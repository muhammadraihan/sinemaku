#!/usr/bin/env node
'use strict';
const path = require('path');
const { chromium } = require(path.resolve(process.env.CINEPOINT_PLAYWRIGHT_PATH || path.join(__dirname, '..', 'node_modules', 'playwright-core')));
const number = value => Number(String(value || '').replace(/[^0-9]/g, ''));
const browserLaunchOptions = {
  headless: true,
  ...(process.env.CINEPOINT_BROWSER_EXECUTABLE
    ? { executablePath: process.env.CINEPOINT_BROWSER_EXECUTABLE }
    : { channel: process.env.CINEPOINT_BROWSER_CHANNEL || 'chrome' }),
};
(async () => {
  const browser = await chromium.launch(browserLaunchOptions);
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
    const entriesByRank = new Map();
    const pages = Math.ceil(total / 10);
    for (let pageNumber = 1; pageNumber <= pages; pageNumber++) {
      if (pageNumber > 1) {
        await page.locator(`app-top-box-office button[aria-label="Page ${pageNumber}"]`).click();
        // Cinepoint keeps transition/previous table nodes in the DOM. Do not depend
        // on one internal cell changing; only collect visible rows and let the strict
        // rank/ID/total validation below decide whether the page actually updated.
        await page.waitForTimeout(2500);
      }
      const rows = await page.locator('app-top-box-office tbody tr:visible').evaluateAll(nodes => nodes.map(row => {
        const cells = [...row.querySelectorAll(':scope > td')];
        const poster = row.querySelector('img')?.src || null;
        const sourceId = poster?.match(/title-([^.?#/]+)/)?.[1] || null;
        return { source_movie_id: sourceId, rank: cells[0]?.innerText.trim(), title: row.querySelector('.text-movie-title-lg')?.textContent.trim(), poster_url: poster?.split('?')[0] || null, daily_admissions: cells[2]?.innerText.trim(), total_admissions: cells[4]?.innerText.trim() };
      }));
      for (const raw of rows) {
        const row = { ...raw, rank: number(raw.rank), daily_admissions: number(raw.daily_admissions), total_admissions: number(raw.total_admissions) };
        if (row.rank >= 1 && row.rank <= total && row.source_movie_id && row.title) entriesByRank.set(row.rank, row);
      }
    }
    const entries = [...entriesByRank.values()].sort((a, b) => a.rank - b.rank);
    const ids = new Set(entries.map(row => row.source_movie_id));
    const ranksComplete = entries.length === total && entries.every((row, index) => row.rank === index + 1);
    if (!ranksComplete || ids.size !== total) throw new Error(`Kelengkapan gagal: rank=${entries.length}/${total}, unik=${ids.size}, sumber=${total}.`);
    process.stdout.write(JSON.stringify({ period_label: match[1], source_total: total, entries }));
  } finally { await browser.close(); }
})().catch(error => { process.stderr.write(error.message + '\n'); process.exit(1); });
