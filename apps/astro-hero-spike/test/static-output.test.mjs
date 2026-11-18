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

test('synthetic article renders shared plain text before JavaScript without interpreting markup', () => {
  const article = readFileSync(path.join(root, 'experiments/article/index.html'), 'utf8');
  assert.match(article, /<meta name="robots" content="noindex, nofollow">/);
  assert.match(article, /<h1[^>]*>Synthetic article<\/h1>/);
  assert.match(article, /A synthetic article for testing the shared editorial layout/);
  assert.match(article, /&lt;strong&gt;literal text&lt;\/strong&gt; &amp; symbols/);
  assert.doesNotMatch(article, /<strong>literal text<\/strong>/);
  assert.doesNotMatch(article, /<link rel="canonical"/);
  assert.doesNotMatch(article, /<astro-island/);
  // The same expected markup is checked against Laravel's PHP-only counterpart.
  const expected = readFileSync(new URL('../../../shared/builder-article-render-fixture.html', import.meta.url), 'utf8').trim().replace(/>\s+</g, '><');
  const paragraphs = article.match(/<p class="text-lg text-muted-foreground[\s\S]*?<\/p>\s*<p class="text-base text-foreground\/85[\s\S]*?<\/p>/);
  assert.ok(paragraphs, 'document renderer must render the actual ArticleText component');
  assert.equal(paragraphs[0].replace(/>\s+</g, '><'), expected);
});

test('synthetic product gallery has no-commerce metadata and real local images before hydration', () => {
  const product = readFileSync(path.join(root, 'experiments/product/index.html'), 'utf8');
  assert.match(product, /<meta name="robots" content="noindex, nofollow">/);
  assert.match(product, /<h1[^>]*>Synthetic product gallery<\/h1>/);
  assert.match(product, /No prices, stock, cart or orders/);
  assert.match(product, /not a real 360° product view/);
  assert.match(product, /alt="Synthetic chair - View 1"/);
  assert.match(product, /alt="Synthetic chair thumbnail 2"/);
  assert.doesNotMatch(product, /\[object Object\]|<link rel="canonical"/);
  const images = [...product.matchAll(/<img src="([^"]+)"/g)].map(match => match[1]);
  assert.equal(images.length, 3);
  assert.equal(new Set(images).size, 2);
  for (const url of images) {
    assert.match(url, /^\/_astro\/[\w.-]+\.webp$/);
    assert.ok(existsSync(path.join(root, url.slice(1))), `missing ${url}`);
  }
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
