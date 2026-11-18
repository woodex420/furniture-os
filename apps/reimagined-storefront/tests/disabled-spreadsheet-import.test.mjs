import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);

test('unmaintained npm xlsx parser is absent from the manifest and complete lockfile', async () => {
  const manifest = JSON.parse(await readFile(new URL('package.json', root), 'utf8'));
  const lock = JSON.parse(await readFile(new URL('package-lock.json', root), 'utf8'));
  for (const group of ['dependencies', 'devDependencies', 'optionalDependencies']) {
    assert.equal(manifest[group]?.xlsx, undefined, `xlsx must not be a ${group} dependency`);
  }
  const xlsx = Object.entries(lock.packages).filter(([path, entry]) => /(?:^|\/)node_modules\/xlsx$/.test(path) || entry.name === 'xlsx');
  assert.deepEqual(xlsx, [], 'xlsx must not be present transitively in the lockfile');
});
