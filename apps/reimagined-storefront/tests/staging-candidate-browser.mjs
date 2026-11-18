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
  assert.match(await page.$eval('body', (el) => el.textContent), /Newsletter signup is not available yet/);
  assert.equal(await page.$('button[aria-label="Open live chat"]'), null);

  // Verify the updated router handles an actual storefront link, query and reload.
  await page.$eval('a[href="/shop?category=chairs"]', el => el.click());
  await page.waitForFunction(() => location.pathname === '/shop' && location.search === '?category=chairs');
  await page.waitForSelector('main');
  const shopResponse = await page.reload({ waitUntil: 'networkidle2' });
  assert.equal(shopResponse.status(), 200);
  assert.equal(new URL(page.url()).search, '?category=chairs');
  await page.waitForSelector('main');

  // Staging mode must not advertise fake successful submissions, even when
  // an event is dispatched directly rather than clicking a disabled control.
  await page.goto(`${origin}/contact`, { waitUntil: 'networkidle2' });
  await page.waitForSelector('#name');
  assert.equal(await page.$eval('#name', (el) => el.matches(':disabled')), true);
  assert.match(await page.$eval('body', (el) => el.textContent), /orders and enquiries are disabled/);
  await page.$eval('#name', (el) => el.closest('form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true })));
  assert.doesNotMatch(await page.$eval('body', (el) => el.textContent), /Message Sent!/);

  await page.evaluate(() => {
    const item = { id: 'synthetic', name: 'Synthetic furniture', category: 'test', price: 100, quantity: 1 };
    localStorage.setItem('woodex-cart', JSON.stringify([item]));
    localStorage.setItem('woodex-quote', JSON.stringify([item]));
  });
  await page.goto(`${origin}/quotation`, { waitUntil: 'networkidle2' });
  await page.waitForSelector('#project-type');
  assert.equal(await page.$eval('#project-type', (el) => el.matches(':disabled')), true);
  assert.equal(await page.evaluate(() => [...document.querySelectorAll('button')].some((el) => el.textContent.includes('Send WhatsApp') && el.disabled)), true);
  await page.$eval('#project-type', (el) => el.closest('form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true })));
  assert.doesNotMatch(await page.$eval('body', (el) => el.textContent), /Quote Request Submitted!/);

  await page.goto(`${origin}/checkout`, { waitUntil: 'networkidle2' });
  await page.waitForSelector('form');
  assert.equal(await page.$eval('form button[type="submit"]', (el) => el.matches(':disabled')), true);
  await page.$eval('form', (el) => el.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true })));
  assert.doesNotMatch(await page.$eval('body', (el) => el.textContent), /Order Placed Successfully!/);
  assert.equal(await page.evaluate(() => JSON.parse(localStorage.getItem('woodex-cart')).length), 1);
  console.log('PASS: packaged storefront, owner login, Livewire, home editor and restricted submissions in synthetic CI browser.');
} finally {
  await browser.close();
}
