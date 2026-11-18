// Disposable GitHub CI only: do not target a real host or use real data.
import assert from 'node:assert/strict';
import puppeteer from 'puppeteer-core';
import chromium from '@sparticuz/chromium';

const origin = 'http://127.0.0.1:8100';
if (process.env.CI !== 'true' || process.env.GITHUB_ACTIONS !== 'true' || process.env.APP_ENV !== 'local' || process.env.DB_DATABASE !== '/tmp/furniture-smoke.sqlite') {
  throw new Error('This test requires the isolated synthetic CI proof fixture.');
}
const browser = await puppeteer.launch({ args: chromium.args, executablePath: await chromium.executablePath(), headless: true });
try {
  const owner = await browser.newPage();
  await owner.goto(origin + '/login', { waitUntil: 'networkidle2' });
  await owner.type('#email', 'owner@example.test');
  await owner.type('#password', 'synthetic-password-for-ci');
  await Promise.all([owner.waitForNavigation({ waitUntil: 'networkidle2' }), owner.click('button[type=submit]')]);
  assert.equal(new URL(owner.url()).pathname, '/admin');
  await owner.goto(origin + '/admin/website/builder-html-proof', { waitUntil: 'networkidle2' });
  // Laravel 13 also accepts a verified same-origin Fetch Metadata header. Test
  // the fallback path without either that header or the CSRF token.
  const cookie = (await owner.cookies()).map(({ name, value }) => `${name}=${value}`).join('; ');
  const missingToken = await fetch(origin + '/admin/website/builder-html-proof', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', Cookie: cookie }, redirect: 'manual',
    body: new URLSearchParams({ revision: '1', summary: 'Bad CSRF', body: 'Should not save' }),
  });
  assert.equal(missingToken.status, 419, 'session write without token or same-origin Fetch Metadata must be rejected');
  const stale = await browser.newPage();
  await stale.setJavaScriptEnabled(false);
  await stale.goto(origin + '/admin/website/builder-html-proof');
  await owner.setJavaScriptEnabled(false);
  await owner.goto(origin + '/admin/website/builder-html-proof');
  await owner.$eval('#proof-summary', el => { el.value = 'Saved synthetic summary'; });
  await owner.$eval('#proof-body', el => { el.value = 'Saved line <strong>not markup</strong> & فرنیچر'; });
  await Promise.all([owner.waitForNavigation(), owner.click('form[action="/admin/website/builder-html-proof"] button[type=submit]')]);
  assert.match(await owner.$eval('body', el => el.textContent), /Synthetic section saved/);
  const raw = await fetch(origin + '/experiments/builder-html-proof');
  assert.match(await raw.text(), /Saved synthetic summary/, 'fresh anonymous HTTP must contain the saved text');
  await stale.$eval('#proof-summary', el => { el.value = 'Stale overwrite'; });
  await Promise.all([stale.waitForNavigation(), stale.click('form[action="/admin/website/builder-html-proof"] button[type=submit]')]);
  assert.match(await stale.$eval('body', el => el.textContent), /Document changed/);
  const structure = 'form[action="/admin/website/builder-html-proof/sections"]';
  await Promise.all([owner.waitForNavigation(), owner.click(`${structure} input[value="add"] + button`)]);
  assert.equal(await owner.$$eval('[data-proof-section]', els => els.length), 2);
  const secondId = (await owner.$$eval('[data-proof-section]', els => els.map(el => el.dataset.proofSection)))[1];
  const section = `[data-proof-section="${secondId}"]`;
  await owner.$eval(`${section} textarea[name=summary]`, el => { el.value = 'Second saved section'; });
  await Promise.all([owner.waitForNavigation(), owner.click(`${section} form[action="/admin/website/builder-html-proof"] button[type=submit]`)]);
  await Promise.all([owner.waitForNavigation(), owner.click(`${section} button[value=up]`)]);
  await Promise.all([owner.waitForNavigation(), owner.click(`${section} button[value=duplicate]`)]);
  assert.equal(await owner.$$eval('[data-proof-section]', els => els.length), 3);
  const ids = await owner.$$eval('[data-proof-section]', els => els.map(el => el.dataset.proofSection));
  assert.equal(ids[0], secondId);
  const duplicateId = ids[1];
  await Promise.all([owner.waitForNavigation(), owner.click(`[data-proof-section="${duplicateId}"] button[value=remove]`)]);
  assert.equal(await owner.$$eval('[data-proof-section]', els => els.length), 2);
  await browser.deleteCookie(...await browser.cookies());
  const crawler = await browser.newPage();
  await crawler.setJavaScriptEnabled(false);
  const response = await crawler.goto(origin + '/experiments/builder-html-proof');
  assert.equal(response.status(), 200);
  assert.match(response.headers()['cache-control'], /no-store/);
  assert.match(response.headers()['x-robots-tag'], /noindex/);
  assert.equal(response.headers()['set-cookie'], undefined);
  const html = await response.text();
  assert.match(html, /Saved synthetic summary/);
  assert.match(html, /&lt;strong&gt;not markup&lt;\/strong&gt; &amp; فرنیچر/);
  assert.doesNotMatch(html, /<strong>not markup<\/strong>/);
  assert.equal(await crawler.$('article strong'), null);
  assert.equal(await crawler.$eval('article', el => el.dataset.builderRevision), '7');
  assert.deepEqual(await crawler.$$eval('article [data-section-id]', els => els.map(el => el.dataset.sectionId)), [secondId, ids[2]]);
  assert.match(await crawler.$eval('article', el => el.textContent), /Second saved section/);
  assert.match(await crawler.$eval('article', el => el.textContent), /Saved synthetic summary/);
  assert.doesNotMatch(await crawler.$eval('article', el => el.textContent), /Stale overwrite/);
  console.log('PASS: isolated owner form, CSRF, stale write and fresh anonymous HTML without JavaScript.');
} finally {
  await browser.close();
}
