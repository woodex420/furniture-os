#!/usr/bin/env node
// Builds an isolated staging CANDIDATE from already installed/built inputs.
// Does not read a database, contact Hostinger, or create credentials.
import { cpSync, existsSync, lstatSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { basename, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

function safeCopy(source, target) {
  cpSync(source, target, {
    recursive: true,
    errorOnExist: true,
    force: false,
    filter(path) {
      const name = basename(path);
      // Installed packages can carry example environment files or VCS metadata.
      if (name === '.git' || name === '.gitignore' || name === '.env' || name.startsWith('.env.')) return false;
      if (lstatSync(path).isSymbolicLink()) throw new Error(`Symlink is not allowed: ${path}`);
      return true;
    },
  });
}

export function buildStagingCandidate({ repoRoot, storefrontFragment, output, revision }) {
  const root = resolve(repoRoot);
  const fragment = resolve(storefrontFragment);
  const destination = resolve(output);
  const app = join(root, 'apps/furniture-platform');
  if (!/^[a-f0-9]{40}$/.test(revision)) throw new Error('A full Git commit SHA is required');
  if (destination === root || destination.startsWith(`${root}/`) || destination === fragment || destination.startsWith(`${fragment}/`)) {
    throw new Error('Output must be outside the repository and storefront fragment');
  }
  const required = [
    'app', 'bootstrap/app.php', 'bootstrap/providers.php', 'config', 'database/migrations',
    'resources', 'routes', 'artisan', 'composer.json', 'composer.lock', 'vendor/autoload.php', 'public/build/manifest.json',
  ];
  for (const item of required) if (!existsSync(join(app, item))) throw new Error(`Missing built application input: ${item}`);
  for (const item of ['public/assets', 'public/images', 'public/og-cover.jpg', 'public/robots.txt', 'private/storefront-preview/index.html']) {
    if (!existsSync(join(fragment, item))) throw new Error(`Missing storefront fragment: ${item}`);
  }
  mkdirSync(destination); // Existing outputs must never be overwritten.
  const privateApp = join(destination, 'furniture-app');
  const publicRoot = join(destination, 'public_html');
  mkdirSync(privateApp);
  mkdirSync(publicRoot);
  for (const item of ['app', 'config', 'resources', 'routes', 'artisan', 'composer.json', 'composer.lock', 'vendor']) {
    safeCopy(join(app, item), join(privateApp, item));
  }
  mkdirSync(join(privateApp, 'bootstrap'));
  for (const item of ['app.php', 'providers.php']) safeCopy(join(app, 'bootstrap', item), join(privateApp, 'bootstrap', item));
  mkdirSync(join(privateApp, 'bootstrap', 'cache'));
  mkdirSync(join(privateApp, 'database'));
  safeCopy(join(app, 'database/migrations'), join(privateApp, 'database/migrations'));
  for (const dir of ['app/private', 'app/public', 'framework/cache/data', 'framework/cache/locks', 'framework/sessions', 'framework/views', 'logs']) {
    mkdirSync(join(privateApp, 'storage', dir), { recursive: true });
  }
  safeCopy(join(fragment, 'private/storefront-preview'), join(privateApp, 'storage/app/storefront-preview'));
  for (const item of ['assets', 'images', 'og-cover.jpg', 'robots.txt']) safeCopy(join(fragment, 'public', item), join(publicRoot, item));
  safeCopy(join(app, 'public/build'), join(publicRoot, 'build'));
  safeCopy(join(root, 'deployment/staging/public/index.php'), join(publicRoot, 'index.php'));
  // safeCopy filters hidden files; this fixed, reviewed file is intentionally included.
  writeFileSync(join(publicRoot, '.htaccess'), readFileSync(join(root, 'deployment/staging/public/.htaccess')), { flag: 'wx' });
  writeFileSync(join(privateApp, '.env.example'), readFileSync(join(root, 'deployment/staging/staging.env.example')), { flag: 'wx' });
  safeCopy(join(root, 'deployment/staging/INSTALL.txt'), join(destination, 'INSTALL.txt'));
  writeFileSync(join(destination, 'BUILD-INFO.txt'), `STAGING CANDIDATE — INCOMPLETE BUSINESS PLATFORM\nCommit: ${revision}\nDatabase SQL must be from the same GitHub Actions run.\n`, { flag: 'wx' });
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  if (process.argv.length !== 5) {
    console.error('Usage: node build-staging-candidate.mjs <repo-root> <storefront-fragment> <new-output-directory>');
    process.exitCode = 1;
  } else {
    try {
      buildStagingCandidate({ repoRoot: process.argv[2], storefrontFragment: process.argv[3], output: process.argv[4], revision: process.env.GITHUB_SHA ?? '' });
      console.log('Built a staging candidate; no files were deployed.');
    } catch (error) {
      console.error(`Candidate build failed: ${error.message}`);
      process.exitCode = 1;
    }
  }
}
