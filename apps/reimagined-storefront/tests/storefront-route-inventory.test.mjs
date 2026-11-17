import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);

test('storefront inventory covers every declared React route exactly once', async () => {
  const app = await readFile(new URL('src/App.tsx', root), 'utf8');
  const inventory = JSON.parse(await readFile(new URL('storefront-route-inventory.json', root), 'utf8'));
  const declared = [...app.matchAll(/<Route\s+path="([^"]+)"/g)].map((match) => match[1]);
  assert.ok(declared.length > 0, 'No literal routes were found; update the inventory checker if routing changes');
  assert.equal(new Set(declared).size, declared.length, 'Duplicate declared route');
  assert.equal(new Set(inventory.map((entry) => entry.path)).size, inventory.length, 'Duplicate inventory route');
  assert.deepEqual(inventory.map((entry) => entry.path).sort(), declared.sort());
  for (const entry of inventory) {
    assert.ok(['partial', 'demo', 'disabled', 'integrated'].includes(entry.status), `Invalid status for ${entry.path}`);
    assert.equal(typeof entry.source, 'string');
    assert.ok(entry.source.length >= 15, `Missing data-source description for ${entry.path}`);
  }
});
