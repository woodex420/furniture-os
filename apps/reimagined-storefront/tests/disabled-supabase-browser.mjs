// Only against a disposable local Vite server configured with synthetic values.
import assert from 'node:assert/strict';
import puppeteer from 'puppeteer-core';
import chromium from '@sparticuz/chromium';

const origin = 'http://127.0.0.1:5199';
const browser = await puppeteer.launch({ args: chromium.args, executablePath: await chromium.executablePath(), headless: true });
try {
  const page = await browser.newPage();
  const external = [];
  await page.setRequestInterception(true);
  page.on('request', request => {
    if (!request.url().startsWith(origin + '/')) { external.push(request.url()); void request.abort(); }
    else void request.continue();
  });
  await page.goto(origin + '/tests/disabled-supabase-harness.html', { waitUntil: 'networkidle2' });
  await page.evaluate(() => localStorage.setItem('woodex.admin.session', JSON.stringify({ email: 'synthetic@example.test', name: 'Synthetic', signedInAt: 'now' })));
  await page.reload({ waitUntil: 'networkidle2' });
  await page.waitForFunction(() => document.querySelector('#auth-probe')?.textContent === 'signed-out' && window.testAuth);
  const importNotice = await page.$eval('section[aria-labelledby="import-title"]', el => el.textContent);
  assert.match(importNotice, /Import is unavailable/);
  assert.equal(await page.$('section[aria-labelledby="import-title"] input, section[aria-labelledby="import-title"] button, section[aria-labelledby="import-title"] form'), null, 'disabled import must not offer upload or commit controls');
  await page.waitForFunction(() => localStorage.getItem('woodex.admin.session') === null);
  const auth = await page.evaluate(async () => ({ demo: await window.testAuth.login('synthetic@example.test', 'synthetic-password'), hosted: await window.testAuth.loginWithSupabase('synthetic@example.test', 'synthetic-password') }));
  assert.equal(auth.demo.ok, false);
  assert.equal(auth.hosted.ok, false);
  assert.match(auth.hosted.error, /disabled/);
  const result = await page.evaluate(() => window.testAdapter());
  assert.deepEqual(result, { mode: 'supabase', enabled: false, url: '', key: '', failures: [true, true, true, true, true], clientBlocked: true });
  assert.equal(external.some(url => /synthetic-provider|unconfigured\.invalid/.test(url)), false, 'legacy client attempted an external request');
  console.log('PASS: unsupported Supabase mode rejects auth, reads and writes; legacy spreadsheet import has no controls.');
} finally {
  await browser.close();
}
