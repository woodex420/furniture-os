import test from 'node:test';
import assert from 'node:assert/strict';
import { evaluatePreflight, minimumPhpExtensions } from './release-preflight.mjs';

const evaluate = (phpVersion, paths = [], composerVersion = '', phpExtensions = []) => evaluatePreflight({
  exists: (path) => paths.includes(path), phpVersion, composerVersion, phpExtensions,
});

test('missing tools and application artifacts are blocked without reading environment data', () => {
  const result = evaluate('');
  assert.equal(result.ready, false);
  assert.equal(result.checks.filter((check) => check.ok).length, 0);
  assert.equal(result.manualGates.length, 5);
});

test('requires PHP 8.4.1 or newer within PHP 8', () => {
  for (const version of ['8.3.29', '8.4.0', '9.0.0', 'unknown']) {
    assert.equal(evaluate(version).checks.find((check) => check.id === 'php').ok, false, version);
  }
  for (const version of ['8.4.1', '8.4.25', '8.5.0']) {
    assert.equal(evaluate(version).checks.find((check) => check.id === 'php').ok, true, version);
  }
});

test('flags missing CLI extensions and accepts case-insensitive reported names', () => {
  const partial = evaluate('8.4.21', [], '', ['pdo_sqlite', 'mbstring', 'curl', 'zip']);
  const check = partial.checks.find((item) => item.id === 'php-extensions');
  assert.equal(check.ok, false);
  assert.match(check.detail, /pdo_mysql/);
  assert.match(check.detail, /xmlwriter/);
  assert.equal(evaluate('8.4.21', [], '', minimumPhpExtensions.map((name) => name.toUpperCase())).checks.find((item) => item.id === 'php-extensions').ok, true);
  assert.equal(evaluate('', [], '', minimumPhpExtensions).checks.find((item) => item.id === 'php-extensions').ok, false);
});

test('recognizes artifacts but never equates their presence with release approval', () => {
  const result = evaluate('8.4.25', [
    'apps/furniture-platform/composer.lock',
    'apps/furniture-platform/vendor/autoload.php',
    'apps/furniture-platform/public/build/manifest.json',
    'apps/storefront/astro.config.mjs',
  ], 'Composer version 2', minimumPhpExtensions);
  assert.ok(result.checks.every((check) => check.ok));
  assert.equal(result.ready, false);
  assert.equal(result.manualGates.length, 5);
});
