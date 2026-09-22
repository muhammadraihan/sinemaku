// Isolated browser regression: real project assets, test-only mocked HTTP.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright-core');
const root = path.resolve(__dirname, '..');

test('real SweetAlert CSS keeps queued/running/success fixed and waits for confirmation', async () => {
    const blade = fs.readFileSync(path.join(root, 'resources/views/seatmap-monitor/cinepoint.blade.php'), 'utf8');
    const cssSection = blade.split("@section('css')")[1].split('@endsection')[0];
    const styles = [...cssSection.matchAll(/asset\('([^']+\.css)'\)/g)].map(m => m[1]);
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    try {
        const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
        let documents = 0, posts = 0, status = 'queued';
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('http://cinepoint.test/**', async route => {
            const url = new URL(route.request().url());
            if (url.pathname === '/sync') { posts++; return route.fulfill({ json: { status: 'queued', job: { id: 7, status: 'queued' }, status_url: '/status' } }); }
            if (url.pathname === '/status') return route.fulfill({ json: { job: { id: 7, status } } });
            if (url.pathname !== '/') return route.fulfill({ path: path.join(root, 'public', url.pathname) });
            documents++;
            return route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head>
                <link rel="stylesheet" href="/css/vendors.bundle.css"><link rel="stylesheet" href="/css/app.bundle.css">
                ${styles.map(s => `<link rel="stylesheet" href="/${s}">`).join('')}
                </head><body><form id="cinepoint-sync-form" action="/sync" method="post"><input name="_token" value="test-only"><button id="cinepoint-sync-button" type="submit">Sync sekarang</button></form><div style="height:4000px">Test-only long ranking fixture</div>
                <script src="/js/notifications/sweetalert2/sweetalert2.bundle.js"></script><script src="/js/cinepoint-sync.js"></script></body></html>` });
        });
        await page.goto('http://cinepoint.test/');
        await page.click('#cinepoint-sync-button');
        async function overlay(text) {
            await page.waitForFunction(t => document.querySelector('.swal2-content')?.textContent.includes(t), text);
            const state = await page.evaluate(() => {
                const container = document.querySelector('.swal2-container');
                const popup = document.querySelector('.swal2-popup');
                const r = popup.getBoundingClientRect();
                return { position: getComputedStyle(container).position, display: getComputedStyle(popup).display, top: r.top, bottom: r.bottom, scroll: scrollY };
            });
            assert.equal(state.position, 'fixed'); assert.notEqual(state.display, 'none');
            assert.ok(state.top >= 0 && state.bottom <= 800, JSON.stringify(state));
            assert.equal(state.scroll, 0); assert.equal(documents, 1);
        }
        await overlay('antrean');
        status = 'running'; await overlay('memproses');
        status = 'success'; await overlay('berhasil disimpan');
        assert.equal(posts, 1);
        await page.waitForTimeout(500); assert.equal(documents, 1);
        await page.click('.swal2-confirm');
        await page.waitForFunction(() => !document.querySelector('.swal2-container'));
        assert.equal(documents, 2); assert.deepEqual(errors, []);
    } finally { await browser.close(); }
});
