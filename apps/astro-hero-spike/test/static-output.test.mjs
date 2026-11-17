import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync, existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = fileURLToPath(new URL('../dist/', import.meta.url));
const html = readFileSync(path.join(root, 'index.html'), 'utf8');

test('Astro renders the real hero and its prototype metadata before JavaScript', () => {
  assert.match(html, /<meta name="robots" content="noindex, nofollow">/);
  assert.match(html, /<h1[^>]*>Design For Your Workspace<\/h1>/);
  assert.match(html, /From concept to installation/);
  assert.match(html, /href="\/shop\?category=workstations"/);
  assert.match(html, /aria-label="Pause slideshow"/);
  assert.match(html, /ASTRO PROTOTYPE/);
  assert.doesNotMatch(html, /\[object Object\]/);
  assert.doesNotMatch(html, /<link rel="canonical"/);
});

test('server-rendered image and stylesheet paths refer to generated files', () => {
  const images = [...html.matchAll(/<img src="([^"]+)"/g)].map((match) => match[1]);
  assert.equal(images.length, 5);
  for (const url of images) {
    assert.match(url, /^\/_astro\/hero-slide-\d\.[\w-]+\.webp$/);
    assert.ok(existsSync(path.join(root, url.slice(1))), `missing ${url}`);
  }
  const css = html.match(/<link rel="stylesheet" href="([^"]+)"/);
  assert.ok(css);
  assert.ok(existsSync(path.join(root, css[1].slice(1))));
});
