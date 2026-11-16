#!/usr/bin/env node
// Read-only local evidence checks. This is not a deployment script or a security certification.
import { existsSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const phpSupported = (version) => {
  const match = /^(\d+)\.(\d+)\.(\d+)/.exec(version ?? '');
  if (!match) return false;
  const [major, minor, patch] = match.slice(1).map(Number);
  return major === 8 && (minor > 4 || (minor === 4 && patch >= 1));
};

// A minimum local CLI check, not an exhaustive Composer or hosting requirements audit.
export const minimumPhpExtensions = ['pdo_mysql', 'pdo_sqlite', 'xmlwriter', 'mbstring', 'curl', 'zip'];

export function evaluatePreflight({ exists, phpVersion, composerVersion, phpExtensions = [] }) {
  const presentExtensions = new Set(phpExtensions.map((extension) => extension.toLowerCase()));
  const missingExtensions = minimumPhpExtensions.filter((extension) => !presentExtensions.has(extension));
  const checks = [
    { id: 'php', ok: phpSupported(phpVersion), detail: 'Native PHP 8.4.1 or later within PHP 8 is available' },
    { id: 'php-extensions', ok: phpSupported(phpVersion) && missingExtensions.length === 0, detail: `Minimum PHP CLI test/staging extensions: ${missingExtensions.length ? `missing ${missingExtensions.join(', ')}` : 'present'} (web runtime and full requirements still unverified)` },
    { id: 'composer', ok: Boolean(composerVersion), detail: 'Native Composer is available' },
    { id: 'composer-lock', ok: exists('apps/furniture-platform/composer.lock'), detail: 'Furniture Laravel Composer lockfile exists' },
    { id: 'vendor', ok: exists('apps/furniture-platform/vendor/autoload.php'), detail: 'Furniture Laravel dependencies exist at application vendor path' },
    { id: 'assets', ok: exists('apps/furniture-platform/public/build/manifest.json'), detail: 'Furniture Laravel Vite manifest exists' },
    { id: 'astro', ok: ['apps/storefront/astro.config.mjs', 'apps/storefront/astro.config.ts', 'apps/reimagin-v1/astro.config.mjs', 'apps/reimagin-v1/astro.config.ts'].some(exists), detail: 'Astro storefront candidate exists at an expected workspace path' },
  ];
  // These require actual recorded evidence, not a marker file, CLI flag or guessed configuration.
  const manualGates = [
    'Integrated Laravel/admin/storefront actions and authenticated browser flows verified',
    'Locked native tests, security review and complete release artifact verified',
    'Correct Hostinger repository, target directory and staging ownership confirmed',
    'Supported MySQL, backups, restore, schema and upgrade tested on isolated staging',
    'Business, technical and security approvals plus separate deployment authorization recorded',
  ];
  return { checks, manualGates, ready: false };
}

function commandOutput(command, args) {
  const result = spawnSync(command, args, { encoding: 'utf8', timeout: 5000, maxBuffer: 16 * 1024 });
  return !result.error && result.status === 0 ? String(result.stdout).trim() : '';
}

function main() {
  const root = resolve(fileURLToPath(new URL('..', import.meta.url)));
  const result = evaluatePreflight({
    exists: (path) => existsSync(join(root, path)),
    phpVersion: commandOutput('php', ['-r', 'echo PHP_VERSION;']),
    phpExtensions: commandOutput('php', ['-r', 'echo implode("\\n", get_loaded_extensions());']).split('\n').filter(Boolean),
    composerVersion: commandOutput('composer', ['--version', '--no-ansi']),
  });
  console.log('WOODEX release preflight — local, read-only');
  for (const check of result.checks) console.log(`${check.ok ? 'PRESENT' : 'BLOCKED'}  ${check.detail}`);
  for (const gate of result.manualGates) console.log(`NOT VERIFIED  ${gate}`);
  console.log('\nNO-GO: this checklist cannot certify readiness; verify the manual gates and update the release process before deployment.');
  console.log('No environment files, database, provider or hosting account were accessed.');
  process.exitCode = 1;
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) main();
