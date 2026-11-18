// Only points at a locally served, isolated Astro build with synthetic content.
import assert from 'node:assert/strict';
import puppeteer from 'puppeteer-core';
import chromium from '@sparticuz/chromium';

const origin = 'http://127.0.0.1:5201';
const browser = await puppeteer.launch({ args: chromium.args, executablePath: await chromium.executablePath(), headless: true });
try {
  const page = await browser.newPage();
  const pageErrors = [];
  page.on('pageerror', error => pageErrors.push(error.message));
  await page.setRequestInterception(true);
  const external = [];
  page.on('request', request => {
    if (!request.url().startsWith(origin + '/')) { external.push(request.url()); void request.abort(); }
    else void request.continue();
  });
  await page.setJavaScriptEnabled(false);
  await page.goto(origin + '/experiments/article/', { waitUntil: 'networkidle0' });
  assert.match(await page.$eval('article', el => el.textContent), /<strong>literal text<\/strong> & symbols/);
  assert.equal(await page.$('article strong'), null, 'article content must be text, not interpreted HTML');
  await page.setJavaScriptEnabled(true);
  for (const width of [320, 375, 768, 1440]) {
    await page.setViewport({ width, height: 900 });
    await page.goto(origin + '/experiments/product/', { waitUntil: 'networkidle0' });
    await page.waitForFunction(() => {
      const island = document.querySelector('[data-prototype-gallery] astro-island');
      return island && !island.hasAttribute('ssr');
    });
    await page.waitForFunction(() => document.querySelector('[data-prototype-gallery] img[alt="Synthetic chair - View 1"]')?.naturalWidth > 0);
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, `horizontal overflow at ${width}px`);
    await page.click('button:has(img[alt="Synthetic chair thumbnail 2"])');
    await page.waitForFunction(() => document.querySelector('[data-prototype-gallery] img[alt="Synthetic chair - View 2"]')?.naturalWidth > 0);
    await page.click('button[aria-label="Previous image"]');
    await page.waitForFunction(() => document.querySelector('[data-prototype-gallery] img[alt="Synthetic chair - View 1"]')?.naturalWidth > 0);
    assert.equal(await page.$('button[title="Add to Cart"], button[title="Add to Quote"]'), null);
  }
  assert.deepEqual(external, [], 'prototype must not contact external services');
  assert.deepEqual(pageErrors, [], 'prototype must not throw page errors');
  console.log('PASS: synthetic Astro editorial text and responsive hydrated shared gallery.');
} finally {
  await browser.close();
}
