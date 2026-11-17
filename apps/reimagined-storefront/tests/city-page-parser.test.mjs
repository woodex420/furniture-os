import test from 'node:test';
import assert from 'node:assert/strict';
import { parseCityIndex, parseCityDetail, slugOk } from '../src/lib/city-page-parser.mjs';

const row = { slug: 'example-city', city_name: 'Example City', title: 'Example title', summary: 'Sample summary' };

test('accepts bounded public pages without carrying unrelated response fields', () => {
  const index = parseCityIndex({ data: [{ ...row, private: 'no' }], current_page: 1, last_page: 1 }, 1);
  assert.deepEqual(index, { data: [row], current_page: 1, last_page: 1 });
  assert.deepEqual(parseCityDetail({ ...row, body: '<script>text</script>', actor_id: 42 }, row.slug), { ...row, body: '<script>text</script>' });
  assert.deepEqual(parseCityIndex({ data: [], current_page: 2, last_page: 1 }, 2)?.data, []);
});

test('rejects invalid routes, partial, duplicate and oversized responses', () => {
  for (const s of ['', 'Example', '../a', '-x', 'a--b', 'a'.repeat(81)]) assert.equal(slugOk(s), false);
  for (const input of [null, {}, { data: [row, row], current_page: 1, last_page: 1 }, { data: [{ ...row, title: 'x'.repeat(121) }], current_page: 1, last_page: 1 }, { data: Array(21).fill(row), current_page: 1, last_page: 1 }, { data: [row], current_page: 2, last_page: 1 }]) assert.equal(parseCityIndex(input, 1), null);
  assert.equal(parseCityDetail({ ...row, body: 'text' }, 'wrong'), null);
  assert.equal(parseCityDetail({ ...row, body: 'x'.repeat(4001) }, row.slug), null);
  assert.equal(parseCityDetail({ ...row, body: '' }, row.slug), null);
});
