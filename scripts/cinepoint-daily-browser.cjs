#!/usr/bin/env node
'use strict';
const path = require('path');
const { chromium } = require(path.resolve(process.env.CINEPOINT_PLAYWRIGHT_PATH || path.join(__dirname, '..', 'node_modules', 'playwright-core')));
const number = value => Number(String(value || '').replace(/[^0-9]/g, ''));
(async () => {
  const browser = await chromium.launch({ channel: process.env.CINEPOINT_BROWSER_CHANNEL || 'chrome', headless: true });
  try {
    const page = await browser.newPage();
    let total = 0;
    let state;
    for (let attempt = 1; attempt <= 3 && !total; attempt++) {
      await page.goto('https://cinepoint.com/', { waitUntil: 'networkidle', timeout: 90000 });
      state = await page.locator('#ng-state').textContent().then(text => Object.values(JSON.parse(text)).find(value => value?.u?.includes('/box-office/daily')));
      total = Number(state?.b?.response_output?.list?.pagination?.total);
      if (!Number.isInteger(total) || total < 1) { total = 0; await page.waitForTimeout(attempt * 1000); }
    }
    const periodText = await page.locator('app-top-box-office').innerText();
    const match = periodText.match(/Period:\s*([A-Za-z]{3}\s+\d{1,2},\s+\d{4})/);
    if (!match) throw new Error('Periode daily publik tidak ditemukan pada halaman Cinepoint.');
    if (!Number.isInteger(total) || total < 1) throw new Error('Total film publik tidak ditemukan pada metadata Cinepoint.');
    const entries = [];
    const pages = Math.ceil(total / 10);
    for (let pageNumber = 1; pageNumber <= pages; pageNumber++) {
      if (pageNumber > 1) {
        await page.locator(`app-top-box-office button[aria-label="Page ${pageNumber}"]`).click();
        await page.waitForTimeout(1500);
      }
      const rows = await page.locator('app-top-box-office tbody tr').evaluateAll(nodes => nodes.map(row => {
        const cells = [...row.querySelectorAll(':scope > td')];
        const poster = row.querySelector('img')?.src || null;
        const sourceId = poster?.match(/title-([^.?#/]+)/)?.[1] || null;
        return { source_movie_id: sourceId, rank: cells[0]?.innerText.trim(), title: row.querySelector('.text-movie-title-lg')?.textContent.trim(), poster_url: poster?.replace(/\?.*$/, '') || null, daily_admissions: cells[2]?.innerText.trim(), total_admissions: cells[4]?.innerText.trim() };
      }));
      entries.push(...rows.map(row => ({ ...row, rank: number(row.rank), daily_admissions: number(row.daily_admissions), total_admissions: number(row.total_admissions) })));
    }
    const ids = new Set(entries.map(row => row.source_movie_id));
    if (entries.length !== total || ids.size !== total || entries.some(row => !row.source_movie_id || !row.title)) throw new Error(`Kelengkapan gagal: DOM=${entries.length}, unik=${ids.size}, sumber=${total}.`);
    process.stdout.write(JSON.stringify({ period_label: match[1], source_total: total, entries }));
  } finally { await browser.close(); }
})().catch(error => { process.stderr.write(error.message + '\n'); process.exit(1); });
