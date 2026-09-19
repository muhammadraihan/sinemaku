const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function harness(responses) {
    let submit, reloads = 0, confirm;
    const popups = [], requests = [], timers = new Map();
    let nextTimer = 0;
    const button = { disabled: false };
    const form = { action: '/sync', querySelector: () => ({ value: 'csrf-test' }), addEventListener: (name, fn) => { submit = fn; } };
    const Swal = {
        fire(options) { popups.push(options); if (options.onOpen) options.onOpen(); return new Promise(resolve => { confirm = resolve; }); },
        update(options) { popups.push(options); }, showLoading() {},
    };
    const context = { document: { getElementById: id => id === 'cinepoint-sync-form' ? form : button },
        window: { Swal, location: { reload() { reloads++; } }, addEventListener() {} }, Swal,
        AbortController, URL, FormData: class {}, Date,
        setTimeout(fn, ms) { const id = ++nextTimer; timers.set(id, { fn, ms }); return id; },
        clearTimeout(id) { timers.delete(id); },
        fetch: async (url, options) => { requests.push({ url, options }); const value = responses.shift(); if (value instanceof Error) throw value; return { ok: true, json: async () => value }; },
    };
    vm.runInNewContext(fs.readFileSync('public/js/cinepoint-sync.js', 'utf8'), context);
    return { button, requests, popups, timers, submit: () => { let prevented = false; submit({ preventDefault() { prevented = true; } }); assert.ok(prevented); },
        confirm: (value = true) => confirm({ value }), reloads: () => reloads,
        async flush() { for (let i = 0; i < 15; i++) await Promise.resolve(); },
        async poll() { const entry = [...timers].find(([, t]) => t.ms === 2000); assert.ok(entry); timers.delete(entry[0]); entry[1].fn(); await this.flush(); },
    };
}

test('queued → running → success: prevents native submit, guards duplicates, reloads only after acknowledgement', async () => {
    const h = harness([{ status: 'queued', job: { id: 7, status: 'queued' }, status_url: '/sync/7' }, { job: { id: 7, status: 'running' } }, { job: { id: 7, status: 'success' } }]);
    h.submit(); h.submit(); await h.flush();
    assert.equal(h.requests.length, 1); assert.equal(h.reloads(), 0); assert.equal(h.button.disabled, true);
    assert.equal(h.requests[0].options.headers['X-CSRF-TOKEN'], 'csrf-test');
    assert.match(h.popups.at(-1).text, /antrean/);
    await h.poll(); assert.match(h.popups.at(-1).text, /memproses/); assert.equal(h.reloads(), 0);
    await h.poll(); assert.equal(h.popups.at(-1).type, 'success'); assert.equal(h.reloads(), 0); assert.equal(h.timers.size, 0);
    h.confirm(); await h.flush(); assert.equal(h.reloads(), 1);
    assert.deepEqual(h.requests.slice(1).map(r => r.url), ['/sync/7', '/sync/7']);
});

test('confirmed job failure offers retry without reload or unsafe server text', async () => {
    const h = harness([{ status: 'queued', job: { id: 7, status: 'queued' }, status_url: '/sync/7' }, { job: { id: 7, status: 'failed', failure_reason: '<script>secret</script>' } }]);
    h.submit(); await h.flush(); await h.poll();
    assert.equal(h.popups.at(-1).type, 'error'); assert.equal(h.popups.at(-1).confirmButtonText, 'Coba lagi');
    assert.doesNotMatch(h.popups.at(-1).text, /secret/); assert.equal(h.reloads(), 0); assert.equal(h.timers.size, 0);
});

test('network failure is unknown, resumes same job without another POST', async () => {
    const h = harness([{ status: 'queued', job: { id: 7, status: 'queued' }, status_url: '/sync/7' }, new Error('network'), { job: { id: 7, status: 'success' } }]);
    h.submit(); await h.flush(); await h.poll();
    assert.equal(h.popups.at(-1).type, 'warning'); assert.match(h.popups.at(-1).text, /belum dapat dipastikan/);
    assert.equal(h.reloads(), 0); assert.equal(h.timers.size, 0);
    h.confirm(); await h.flush();
    assert.equal(h.requests.filter(r => r.options.method === 'POST').length, 1);
    assert.equal(h.popups.at(-1).type, 'success');
});
