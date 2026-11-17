import test from 'node:test';
import assert from 'node:assert/strict';
import { parseServiceCopy } from '../src/lib/service-copy-parser.mjs';

const slugs = ['one', 'two'];
const valid = () => [{ slug: 'one', title: null, tagline: null }, { slug: 'two', title: 'Title', tagline: 'Text' }];

test('complete snapshots accept saved text and null default independently', () => {
  assert.deepEqual(parseServiceCopy(valid(), slugs), { two: { title: 'Title', tagline: 'Text' } });
  assert.deepEqual(parseServiceCopy(valid().map(row => ({ ...row, title: null, tagline: null })), slugs), {});
});

test('partial, duplicate, unknown and invalid snapshots fail closed', () => {
  for (const input of [null, {}, [], [valid()[0]], [valid()[0], valid()[0]], [valid()[0], { slug: 'third', title: 'x', tagline: 'y' }], [valid()[0], { slug: 'two', title: null, tagline: 'x' }], [valid()[0], { slug: 'two', title: ' ', tagline: 'x' }], [valid()[0], { slug: 'two', title: 'a'.repeat(121), tagline: 'x' }], [valid()[0], { slug: 'two', title: 'x', tagline: 'a'.repeat(241) }]]) {
    assert.equal(parseServiceCopy(input, slugs), null);
  }
});

test('unicode and plain text are retained without being interpreted as markup', () => {
  const input = valid();
  input[1] = { slug: 'two', title: '🪑'.repeat(120), tagline: '<img src=x onerror=alert(1)>' };
  assert.equal(parseServiceCopy(input, slugs).two.tagline, input[1].tagline);
  input[1].title += '🪑';
  assert.equal(parseServiceCopy(input, slugs), null);
});
