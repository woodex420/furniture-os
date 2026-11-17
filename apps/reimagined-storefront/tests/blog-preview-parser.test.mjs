import test from 'node:test';
import assert from 'node:assert/strict';
import { parseBlogIndex, parseBlogDetail, slugOk } from '../src/lib/blog-preview-parser.mjs';

const row = { slug: 'example-article', title: 'Example article', summary: 'Sample summary' };

test('accepts bounded public articles without carrying private fields', () => {
  assert.deepEqual(parseBlogIndex({ data: [{ ...row, body: 'hidden', actor_id: 5 }], current_page: 1, last_page: 1 }, 1), { data: [row], current_page: 1, last_page: 1 });
  assert.deepEqual(parseBlogDetail({ ...row, body: '<script>text</script>', actor_id: 5 }, row.slug), { ...row, body: '<script>text</script>' });
  assert.deepEqual(parseBlogIndex({ data: [], current_page: 2, last_page: 1 }, 2)?.data, []);
});

test('rejects bad slugs, duplicate, partial and oversized responses', () => {
  for (const s of ['', 'Example', '../a', '-x', 'a--b', 'a'.repeat(81)]) assert.equal(slugOk(s), false);
  for (const input of [null, {}, { data: [row, row], current_page: 1, last_page: 1 }, { data: [{ ...row, title: 'x'.repeat(161) }], current_page: 1, last_page: 1 }, { data: Array(13).fill(row), current_page: 1, last_page: 1 }, { data: [row], current_page: 2, last_page: 1 }]) assert.equal(parseBlogIndex(input, 1), null);
  assert.equal(parseBlogDetail({ ...row, body: 'text' }, 'wrong'), null);
  assert.equal(parseBlogDetail({ ...row, body: 'x'.repeat(12001) }, row.slug), null);
  assert.equal(parseBlogDetail({ ...row, body: '' }, row.slug), null);
});

test('bounded unicode and markup-like text are treated as plain strings', () => {
  const body = '🪑'.repeat(12000);
  assert.equal(parseBlogDetail({ ...row, body }, row.slug).body, body);
  assert.equal(parseBlogDetail({ ...row, body: body + '🪑' }, row.slug), null);
});
