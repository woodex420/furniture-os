import test from 'node:test';
import assert from 'node:assert/strict';
import { existsSync, lstatSync, mkdirSync, mkdtempSync, readFileSync, rmSync, symlinkSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { finalizeStagingBuild } from '../scripts/finalize-staging-build.mjs';

function fixture(t) {
  const dir = mkdtempSync(join(tmpdir(), 'furniture-finalize-'));
  t.after(() => rmSync(dir, { force: true, recursive: true }));
  mkdirSync(join(dir, 'assets'));
  writeFileSync(join(dir, 'index.html'), '<meta name="robots" content="noindex, nofollow" /><meta name="furniture-staging" content="restricted">');
  writeFileSync(join(dir, 'robots.txt'), 'Sitemap: https://example.invalid');
  writeFileSync(join(dir, 'sitemap.xml'), '<urlset/>');
  writeFileSync(join(dir, '.htaccess'), 'old rewrite');
  return dir;
}

test('finalizes restricted output without retaining source sitemap or rewrite', (t) => {
  const dir = fixture(t);
  finalizeStagingBuild(dir);
  assert.equal(existsSync(join(dir, 'sitemap.xml')), false);
  assert.equal(existsSync(join(dir, '.htaccess')), false);
  assert.equal(readFileSync(join(dir, 'robots.txt'), 'utf8'), 'User-agent: *\nDisallow: /\n');
  finalizeStagingBuild(dir);
});

test('fails closed before deleting files when staging markers or assets are missing', (t) => {
  const dir = fixture(t);
  writeFileSync(join(dir, 'index.html'), '<html></html>');
  assert.throws(() => finalizeStagingBuild(dir), /staging and noindex markers/);
  assert.equal(existsSync(join(dir, 'sitemap.xml')), true);
  writeFileSync(join(dir, 'index.html'), '<meta name="robots" content="noindex, nofollow" /><meta name="furniture-staging" content="restricted">');
  rmSync(join(dir, 'assets'), { recursive: true });
  assert.throws(() => finalizeStagingBuild(dir));
  assert.equal(existsSync(join(dir, 'sitemap.xml')), true);
});

test('rejects even a dangling symlink before changing build output', (t) => {
  const dir = fixture(t);
  rmSync(join(dir, 'robots.txt'));
  symlinkSync(join(dir, 'missing-target'), join(dir, 'robots.txt'));
  assert.throws(() => finalizeStagingBuild(dir), /regular file/);
  assert.equal(existsSync(join(dir, 'sitemap.xml')), true);
  assert.equal(lstatSync(join(dir, 'robots.txt')).isSymbolicLink(), true);
});
