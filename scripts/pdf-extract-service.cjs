#!/usr/bin/env node
'use strict';

/*
 * Sinemaku PDF text extraction service (VPS side).
 *
 * Purpose: shared hosting cannot install Poppler (`pdftotext`), so this small
 * dependency-free HTTP service performs layout extraction on a VPS and returns
 * plain text to the Laravel application over HTTPS.
 *
 * Security: every request is authenticated with HMAC-SHA256 over
 *   timestamp + "\n" + sha256(raw body)
 * using a shared secret read from a protected file. A five-minute window is
 * enforced. Request bodies are never logged. Only public distributor report
 * PDFs are sent; nothing is persisted on disk beyond a temporary file that is
 * removed immediately after extraction.
 *
 * Environment (see docs/pdf-vps-extraction-deployment.md):
 *   PDF_EXTRACT_BIND=127.0.0.1        address to listen on (put a TLS proxy in front)
 *   PDF_EXTRACT_PORT=8791
 *   PDF_EXTRACT_SECRET_FILE=/etc/sinemaku-pdf-extract.secret
 *   PDF_EXTRACT_PDFTOTEXT=/usr/bin/pdftotext
 *   PDF_EXTRACT_MAX_BYTES=26214400    reject larger uploads (25 MiB)
 *   PDF_EXTRACT_TIMEOUT_MS=60000
 */

const http = require('http');
const crypto = require('crypto');
const fs = require('fs');
const os = require('os');
const path = require('path');
const { execFile } = require('child_process');

const BIND = process.env.PDF_EXTRACT_BIND || '127.0.0.1';
const PORT = Number(process.env.PDF_EXTRACT_PORT || 8791);
const SECRET_FILE = process.env.PDF_EXTRACT_SECRET_FILE || '';
const PDFTOTEXT = process.env.PDF_EXTRACT_PDFTOTEXT || 'pdftotext';
const MAX_BYTES = Number(process.env.PDF_EXTRACT_MAX_BYTES || 26214400);
const TIMEOUT_MS = Number(process.env.PDF_EXTRACT_TIMEOUT_MS || 60000);
const WINDOW_SECONDS = 300;

function fail(res, status, message) {
    res.writeHead(status, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ ok: false, message: message }));
}

function loadSecret() {
    if (!SECRET_FILE) throw new Error('PDF_EXTRACT_SECRET_FILE belum diatur.');
    const value = fs.readFileSync(SECRET_FILE, 'utf8').trim();
    if (value.length < 32) throw new Error('Secret terlalu pendek (minimal 32 karakter).');
    return value;
}

const SECRET = loadSecret();

function extract(pdfPath) {
    return new Promise((resolve, reject) => {
        execFile(PDFTOTEXT, ['-layout', pdfPath, '-'], { timeout: TIMEOUT_MS, maxBuffer: 64 * 1024 * 1024 },
            (error, stdout) => {
                if (error && !stdout) {
                    const notFound = error.code === 'ENOENT'
                        || /not found|No such file/i.test(String(error.message));
                    reject(notFound
                        ? new Error('pdftotext tidak tersedia pada VPS (' + PDFTOTEXT + ').')
                        : new Error('Ekstraksi gagal: ' + String(error.message)));
                    return;
                }
                resolve(stdout);
            });
    });
}

function constantTimeEqual(a, b) {
    const left = Buffer.from(String(a));
    const right = Buffer.from(String(b));
    if (left.length !== right.length) return false;
    return crypto.timingSafeEqual(left, right);
}

const server = http.createServer((req, res) => {
    if (req.method === 'GET' && req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ ok: true, service: 'sinemaku-pdf-extract' }));
        return;
    }

    if (req.method !== 'POST' || req.url !== '/extract') {
        fail(res, 404, 'Rute tidak ditemukan.');
        return;
    }

    const timestamp = String(req.headers['x-pdf-extract-timestamp'] || '');
    const signature = String(req.headers['x-pdf-extract-signature'] || '');
    if (!/^\d{10}$/.test(timestamp) || Math.abs(Math.floor(Date.now() / 1000) - Number(timestamp)) > WINDOW_SECONDS) {
        fail(res, 401, 'Timestamp tidak valid.');
        return;
    }

    const chunks = [];
    let size = 0;
    let aborted = false;
    req.on('data', (chunk) => {
        size += chunk.length;
        if (size > MAX_BYTES) {
            aborted = true;
            fail(res, 413, 'Ukuran berkas melebihi batas.');
            req.destroy();
            return;
        }
        chunks.push(chunk);
    });

    req.on('end', () => {
        if (aborted) return;
        const body = Buffer.concat(chunks);
        if (body.length === 0) {
            fail(res, 422, 'Badan permintaan kosong.');
            return;
        }

        const expected = crypto.createHmac('sha256', SECRET)
            .update(timestamp + '\n' + crypto.createHash('sha256').update(body).digest('hex'))
            .digest('hex');
        if (!constantTimeEqual(expected, signature)) {
            fail(res, 401, 'Tanda tangan tidak valid.');
            return;
        }

        const file = path.join(os.tmpdir(), 'pdf-extract-' + crypto.randomBytes(12).toString('hex'));
        fs.writeFile(file, body, (writeError) => {
            if (writeError) {
                fail(res, 500, 'Berkas sementara tidak dapat dibuat.');
                return;
            }
            extract(file).then((text) => {
                fs.unlink(file, () => {});
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ ok: true, text: text }));
            }).catch((error) => {
                fs.unlink(file, () => {});
                fail(res, 422, error.message);
            });
        });
    });
});

server.headersTimeout = 30000;
server.requestTimeout = TIMEOUT_MS + 15000;

server.listen(PORT, BIND, () => {
    process.stdout.write('sinemaku pdf-extract listening on ' + BIND + ':' + PORT + '\n');
});
