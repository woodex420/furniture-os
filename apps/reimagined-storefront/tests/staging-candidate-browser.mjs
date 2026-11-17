// CI-only browser test against the extracted synthetic staging candidate.
// Never points at Hostinger, external domains or real credentials.
import assert from 'node:assert/strict';
import puppeteer from 'puppeteer-core';
import chromium from '@sparticuz/chromium';

const origin = process.env.FURNITURE_CANDIDATE_TEST_ORIGIN;
if (process.env.CI !== 'true' || process.env.GITHUB_ACTIONS !== 'true' || origin !== 'http://127.0.0.1:8098') {
  throw new Error('This browser test only runs against the disposable GitHub CI candidate.');
}

const browser = await puppeteer.launch({ args: chromium.args, executablePath: await chromium.executablePath(), headless: true });
try {
  const page = await browser.newPage();
  page.setDefaultTimeout(20000);
  await page.goto(`${origin}/login`, { waitUntil: 'networkidle2' });
  await page.type('#email', 'owner@example.test');
  await page.type('#password', 'synthetic-password-for-ci');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2' }),
    page.click('button[type="submit"]'),
  ]);
  assert.equal(new URL(page.url()).pathname, '/admin', 'Owner login did not reach the dashboard');
  assert.match(await page.title(), /Dashboard/);

  await page.goto(`${origin}/admin/leads`, { waitUntil: 'networkidle2' });
  const livewireUpdate = page.waitForResponse((response) => response.url().startsWith(`${origin}/workspace/livewire/update`) && response.request().method() === 'POST' && response.status() === 200);
  await page.type('input[placeholder="Search leads..."]', 'synthetic-search');
  await livewireUpdate;
  await page.waitForFunction(() => document.body.textContent.includes('Try another search or status.'));
  await page.goto(`${origin}/admin/leads`, { waitUntil: 'networkidle2' });
  const statusUpdate = page.waitForResponse((response) => response.url().startsWith(`${origin}/workspace/livewire/update`) && response.request().method() === 'POST' && response.status() === 200);
  await page.select('select[name="status"]', 'qualified');
  await statusUpdate;
  await page.waitForFunction(() => document.body.textContent.includes('Synthetic qualified enquiry') && !document.body.textContent.includes('Synthetic new enquiry'));

  await page.goto(`${origin}/admin/website/home`, { waitUntil: 'networkidle2' });
  await page.$eval('#hero-title', (el) => { el.value = ''; });
  await page.type('#hero-title', 'Synthetic staging headline');
  await page.$eval('#hero-subtitle', (el) => { el.value = ''; });
  await page.type('#hero-subtitle', 'Synthetic staging subtitle');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2' }),
    page.click('form[action="/admin/website/home"] button[type="submit"]'),
  ]);
  assert.match(await page.$eval('body', (el) => el.textContent), /Home slide updated/);
  const response = await page.goto(`${origin}/storefront-api/home`, { waitUntil: 'networkidle2' });
  const content = JSON.parse(await response.text());
  assert.equal(content.hero_title, 'Synthetic staging headline');
  await page.goto(`${origin}/`, { waitUntil: 'networkidle2' });
  await page.waitForFunction(() => document.body.textContent.includes('Synthetic staging headline'));
  console.log('PASS: packaged storefront, owner login, Livewire search and home editor in synthetic CI browser.');
} finally {
  await browser.close();
}
