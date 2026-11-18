import { test } from 'node:test';
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { readFileSync, readdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = fileURLToPath(new URL('../', import.meta.url));
const config = JSON.parse(readFileSync(path.join(root, 'vercel.json'), 'utf8'));

test('Vercel builds only the dashboard output with explicit noindex headers', () => {
  assert.equal(config.outputDirectory, 'apps/dashboard/dist');
  assert.equal(config.buildCommand, 'npm run build');
  assert.equal(config.ignoreCommand, 'node scripts/vercel-ignore-production.mjs');
  assert.ok(config.headers.some(rule => rule.source === '/(.*)' && rule.headers.some(header => header.key === 'X-Robots-Tag' && header.value === 'noindex, nofollow')));
  const output = path.join(root, config.outputDirectory);
  assert.deepEqual(readdirSync(output).sort(), ['assets', 'index.html', 'robots.txt']);
  assert.match(readFileSync(path.join(output, 'index.html'), 'utf8'), /<meta name="robots" content="noindex, nofollow"\s*\/?\s*>/);
  assert.match(readFileSync(path.join(output, 'robots.txt'), 'utf8'), /Disallow: \/\s*$/);
  assert.doesNotMatch(readFileSync(path.join(output, 'index.html'), 'utf8'), /<\?php|APP_KEY|DB_PASSWORD/);
});

test('build guard permits preview only and fails closed for production or unknown targets', () => {
  for (const [target, exit] of [['preview', 1], ['production', 0], ['development', 0], ['', 0], ['staging', 0]]) {
    const result = spawnSync(process.execPath, [path.join(root, 'scripts/vercel-ignore-production.mjs')], { env: { ...process.env, VERCEL_ENV: target }, encoding: 'utf8' });
    assert.equal(result.status, exit, `unexpected decision for ${target}`);
  }
});
