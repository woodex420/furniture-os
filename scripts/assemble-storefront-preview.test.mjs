import test from 'node:test';
import assert from 'node:assert/strict';
import { mkdtempSync, mkdirSync, readFileSync, writeFileSync, existsSync, symlinkSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { assembleStorefrontPreview } from './assemble-storefront-preview.mjs';

function fixture(t) {
  const root = mkdtempSync(join(tmpdir(), 'furniture-assemble-'));
  t.after(() => rmSync(root, { recursive: true, force: true }));
  const dist = join(root, 'dist');
  mkdirSync(dist);
  mkdirSync(join(dist, 'assets'));
  mkdirSync(join(dist, 'images'));
  writeFileSync(join(dist, 'assets', 'app.js'), 'example');
  writeFileSync(join(dist, 'images', 'chair.webp'), 'example');
  writeFileSync(join(dist, 'og-cover.jpg'), 'example');
  writeFileSync(join(dist, 'index.html'), '<meta name="robots" content="noindex, nofollow" />');
  writeFileSync(join(dist, 'sitemap.xml'), 'production sitemap');
  writeFileSync(join(dist, '.htaccess'), 'untrusted source rewrite');
  return { root, dist, output: join(root, 'out') };
}

test('assembles only staging files with private index and disallow robots', (t) => {
  const { dist, output } = fixture(t);
  assembleStorefrontPreview(dist, output);
  assert.equal(readFileSync(join(output, 'public', 'assets', 'app.js'), 'utf8'), 'example');
  assert.match(readFileSync(join(output, 'private', 'storefront-preview', 'index.html'), 'utf8'), /noindex/);
  assert.equal(readFileSync(join(output, 'public', 'robots.txt'), 'utf8'), 'User-agent: *\nDisallow: /\n');
  for (const name of ['index.html', 'sitemap.xml', '.htaccess']) assert.equal(existsSync(join(output, 'public', name)), false);
  assert.throws(() => assembleStorefrontPreview(dist, output), /EEXIST/);
});

test('rejects executable and symlink assets before creating output', (t) => {
  const { dist, output } = fixture(t);
  writeFileSync(join(dist, 'assets', 'bad.php'), '<?php');
  assert.throws(() => assembleStorefrontPreview(dist, output), /Unexpected asset/);
  assert.equal(existsSync(output), false);
  rmSync(join(dist, 'assets', 'bad.php'));
  symlinkSync(join(dist, 'og-cover.jpg'), join(dist, 'images', 'linked.jpg'));
  assert.throws(() => assembleStorefrontPreview(dist, output), /Symlink/);
  assert.equal(existsSync(output), false);
});

test('rejects unmarked shell and output nested in source', (t) => {
  const { dist, output } = fixture(t);
  writeFileSync(join(dist, 'index.html'), '<html></html>');
  assert.throws(() => assembleStorefrontPreview(dist, output), /noindex/);
  assert.throws(() => assembleStorefrontPreview(dist, join(dist, 'out')), /inside/);
  assert.equal(existsSync(output), false);
});
