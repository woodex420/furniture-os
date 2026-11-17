import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const root = new URL('../', import.meta.url);

test('Laravel shell route matches every inventoried storefront route and not reserved paths', () => {
  const routes = readFileSync(new URL('apps/furniture-platform/routes/web.php', root), 'utf8');
  const match = routes.match(/->where\('storefrontPath', '([^']+)'\)/);
  assert.ok(match, 'No storefront route constraint found');
  const allowed = new RegExp(`^(?:${match[1]})$`);
  const inventory = JSON.parse(readFileSync(new URL('apps/reimagined-storefront/storefront-route-inventory.json', root), 'utf8'));
  for (const { path } of inventory) {
    if (path === '/' || path === '*') continue;
    const example = path.slice(1).replace(/:[^/]+/g, 'example');
    assert.match(example, allowed, `Laravel route missing ${path}`);
  }
  for (const reserved of ['admin', 'admin/leads', 'login', 'storefront-api/home', 'livewire/update', 'random', 'shop/example/extra']) {
    assert.doesNotMatch(reserved, allowed, `Unexpected match for ${reserved}`);
  }
});
