#!/usr/bin/env node
// Assemble only the Reimagin build portion of a SAME-ORIGIN staging candidate.
// This does not install Laravel, configure hosting, or create a deployable release.
import { lstatSync, readdirSync, readFileSync, mkdirSync, copyFileSync, writeFileSync } from 'node:fs';
import { basename, extname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const allowedExtensions = new Set(['.js', '.css', '.webp', '.png', '.jpg', '.jpeg', '.svg', '.woff', '.woff2', '.ico']);

function inspectTree(path) {
  const stat = lstatSync(path);
  if (stat.isSymbolicLink()) throw new Error(`Symlink is not allowed: ${path}`);
  if (stat.isDirectory()) {
    for (const name of readdirSync(path)) {
      if (name.startsWith('.')) throw new Error(`Hidden asset is not allowed: ${name}`);
      inspectTree(join(path, name));
    }
  } else if (!stat.isFile() || !allowedExtensions.has(extname(path).toLowerCase())) {
    throw new Error(`Unexpected asset file: ${path}`);
  }
}

function copyTree(source, target) {
  const stat = lstatSync(source);
  if (stat.isDirectory()) {
    mkdirSync(target);
    for (const name of readdirSync(source)) copyTree(join(source, name), join(target, name));
  } else {
    copyFileSync(source, target);
  }
}

export function assembleStorefrontPreview(dist, output) {
  dist = resolve(dist);
  output = resolve(output);
  if (output === dist || output.startsWith(`${dist}/`)) throw new Error('Output cannot be inside the source bundle');
  for (const name of ['assets', 'images', 'og-cover.jpg']) inspectTree(join(dist, name));
  const indexPath = join(dist, 'index.html');
  if (!lstatSync(indexPath).isFile() || lstatSync(indexPath).isSymbolicLink()) throw new Error('Invalid storefront index');
  const html = readFileSync(indexPath, 'utf8');
  if (!/<meta\s+name="robots"\s+content="noindex,\s*nofollow"\s*\/?\s*>/i.test(html)) {
    throw new Error('Preview index must contain a noindex, nofollow robots tag');
  }
  // mkdir without recursive or overwrite means an existing directory is never modified.
  mkdirSync(output);
  mkdirSync(join(output, 'public'));
  mkdirSync(join(output, 'private'));
  mkdirSync(join(output, 'private', 'storefront-preview'));
  for (const name of ['assets', 'images', 'og-cover.jpg']) copyTree(join(dist, name), join(output, 'public', name));
  writeFileSync(join(output, 'private', 'storefront-preview', 'index.html'), html, { flag: 'wx' });
  writeFileSync(join(output, 'public', 'robots.txt'), 'User-agent: *\nDisallow: /\n', { flag: 'wx' });
  writeFileSync(join(output, 'README.txt'), [
    'EXPERIMENTAL STOREFRONT FILES ONLY - NOT A DEPLOYABLE APPLICATION.',
    'Requires a separately assembled Laravel application, static-file mapping, access control and hosting tests.',
    'Noindex is not access control. No real business data or transactions.',
    'The private storefront-preview folder must never be placed in the web root.',
    'Source sitemap and .htaccess are intentionally excluded.',
    '',
  ].join('\n'), { flag: 'wx' });
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  if (process.argv.length !== 4) {
    console.error(`Usage: node ${basename(process.argv[1])} <built-storefront-dist> <new-output-directory>`);
    process.exitCode = 1;
  } else {
    try {
      assembleStorefrontPreview(process.argv[2], process.argv[3]);
      console.log('Assembled experimental storefront files. This is not an installable release.');
    } catch (error) {
      console.error(`Assembly failed: ${error.message}`);
      process.exitCode = 1;
    }
  }
}
