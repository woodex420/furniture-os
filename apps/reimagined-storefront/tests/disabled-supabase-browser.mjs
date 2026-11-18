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
  await page.goto(origin + '/', { waitUntil: 'networkidle2' });
  await page.evaluate(async () => {
    localStorage.setItem('woodex.admin.session', JSON.stringify({ email: 'synthetic@example.test', name: 'Synthetic', signedInAt: 'now' }));
    const harness = await import('/tests/disabled-supabase-harness.tsx');
    harness.mount();
  });
  await page.waitForFunction(() => document.querySelector('#auth-probe')?.textContent === 'signed-out' && window.testAuth);
  await page.waitForFunction(() => localStorage.getItem('woodex.admin.session') === null);
  const auth = await page.evaluate(async () => ({ demo: await window.testAuth.login('synthetic@example.test', 'synthetic-password'), hosted: await window.testAuth.loginWithSupabase('synthetic@example.test', 'synthetic-password') }));
  assert.equal(auth.demo.ok, false);
  assert.equal(auth.hosted.ok, false);
  assert.match(auth.hosted.error, /disabled/);
  const result = await page.evaluate(async () => {
    const { config } = await import('/src/config/env.ts');
    const { getAdminApi } = await import('/src/api/index.ts');
    const { getPublishedPage, getPagePreview, listPublishedProjects } = await import('/src/api/supabase.ts');
    const { supabase } = await import('/src/integrations/supabase/client.ts');
    const failures = [];
    for (const operation of [() => getAdminApi().listProducts(), () => getAdminApi().createLead({}), () => getPublishedPage('home'), () => getPagePreview('home'), () => listPublishedProjects()]) {
      try { await operation(); failures.push(false); } catch { failures.push(true); }
    }
    // Supabase JS may return an error object rather than throw on transport failure.
    let clientBlocked = false;
    try { const response = await supabase.from('synthetic').select(); clientBlocked = Boolean(response.error); } catch { clientBlocked = true; }
    return { mode: config.apiMode, enabled: config.supabase.enabled, url: config.supabase.url, key: config.supabase.publishableKey, failures, clientBlocked };
  });
  assert.deepEqual(result, { mode: 'supabase', enabled: false, url: '', key: '', failures: [true, true, true, true, true], clientBlocked: true });
  assert.equal(external.some(url => /synthetic-provider|unconfigured\.invalid/.test(url)), false, 'legacy client attempted an external request');
  console.log('PASS: unsupported Supabase mode rejects auth, reads and writes without demo fallback or provider network access.');
} finally {
  await browser.close();
}
