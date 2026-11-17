import test from 'node:test';
import assert from 'node:assert/strict';
import { existsSync, mkdirSync, mkdtempSync, readFileSync, rmSync, symlinkSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { buildStagingCandidate } from './build-staging-candidate.mjs';

function fixture(t) {
  const base = mkdtempSync(join(tmpdir(), 'furniture-package-'));
  t.after(() => rmSync(base, { recursive: true, force: true }));
  const root = join(base, 'repo');
  const fragment = join(base, 'fragment');
  const output = join(base, 'output');
  function file(path, content = 'fixture') {
    mkdirSync(dirname(path), { recursive: true });
    writeFileSync(path, content);
  }
  const app = join(root, 'apps/furniture-platform');
  for (const name of ['app/Model.php', 'bootstrap/app.php', 'bootstrap/providers.php', 'config/app.php', 'database/migrations/one.php', 'resources/views/main.blade.php', 'routes/web.php', 'artisan', 'composer.json', 'composer.lock', 'vendor/autoload.php', 'public/build/manifest.json']) file(join(app, name));
  file(join(app, '.env'), 'DO NOT PACKAGE');
  file(join(app, 'vendor/.env.example'), 'DO NOT PACKAGE');
  file(join(app, 'storage/logs/laravel.log'), 'DO NOT PACKAGE');
  for (const name of ['public/assets/app.js', 'public/images/chair.webp', 'public/og-cover.jpg', 'public/robots.txt', 'private/storefront-preview/index.html']) file(join(fragment, name));
  for (const name of ['public/index.php', 'public/.htaccess', 'staging.env.example', 'INSTALL.txt']) file(join(root, 'deployment/staging', name));
  return { root, fragment, output, app };
}
const sha = 'a'.repeat(40);

test('assembles an isolated, reproducible layout without runtime data or secrets', (t) => {
  const f = fixture(t);
  buildStagingCandidate({ repoRoot: f.root, storefrontFragment: f.fragment, output: f.output, revision: sha });
  assert.equal(existsSync(join(f.output, 'furniture-app/vendor/autoload.php')), true);
  assert.equal(existsSync(join(f.output, 'furniture-app/.env')), false);
  assert.equal(existsSync(join(f.output, 'furniture-app/vendor/.env.example')), false);
  assert.equal(existsSync(join(f.output, 'furniture-app/storage/logs/laravel.log')), false);
  assert.equal(existsSync(join(f.output, 'furniture-app/.env.example')), true);
  assert.equal(existsSync(join(f.output, 'public_html/index.php')), true);
  assert.equal(existsSync(join(f.output, 'public_html/.htaccess')), true);
  assert.equal(existsSync(join(f.output, 'public_html/build/manifest.json')), true);
  assert.equal(existsSync(join(f.output, 'public_html/sitemap.xml')), false);
  assert.equal(existsSync(join(f.output, 'public_html/storage')), false);
  assert.match(readFileSync(join(f.output, 'BUILD-INFO.txt'), 'utf8'), new RegExp(sha));
  assert.throws(() => buildStagingCandidate({ repoRoot: f.root, storefrontFragment: f.fragment, output: f.output, revision: sha }), /EEXIST/);
});

test('requires exact revision and refuses repository outputs or incomplete inputs', (t) => {
  const f = fixture(t);
  assert.throws(() => buildStagingCandidate({ repoRoot: f.root, storefrontFragment: f.fragment, output: f.output, revision: 'abc' }), /commit SHA/);
  assert.throws(() => buildStagingCandidate({ repoRoot: f.root, storefrontFragment: f.fragment, output: join(f.root, 'out'), revision: sha }), /outside/);
  rmSync(join(f.app, 'vendor/autoload.php'));
  assert.throws(() => buildStagingCandidate({ repoRoot: f.root, storefrontFragment: f.fragment, output: f.output, revision: sha }), /Missing built application/);
  assert.equal(existsSync(f.output), false);
});

test('does not copy symbolic links', (t) => {
  const f = fixture(t);
  symlinkSync(join(f.app, 'composer.json'), join(f.app, 'app/linked.php'));
  assert.throws(() => buildStagingCandidate({ repoRoot: f.root, storefrontFragment: f.fragment, output: f.output, revision: sha }), /Symlink/);
});
