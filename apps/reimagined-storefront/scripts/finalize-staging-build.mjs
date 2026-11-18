#!/usr/bin/env node
// A restricted preview build is not a production storefront or sitemap.
import { lstatSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

function requireRegularFile(path) {
  const stat = lstatSync(path);
  if (stat.isSymbolicLink() || !stat.isFile()) throw new Error(`Expected a regular file: ${path}`);
}

export function finalizeStagingBuild(directory) {
  const dist = resolve(directory);
  const dir = lstatSync(dist);
  if (dir.isSymbolicLink() || !dir.isDirectory()) throw new Error('Build output must be a real directory');
  const index = join(dist, 'index.html');
  requireRegularFile(index);
  const html = readFileSync(index, 'utf8');
  if (!/<meta\s+name="furniture-staging"\s+content="restricted"\s*\/?\s*>/i.test(html) ||
      !/<meta\s+name="robots"\s+content="noindex,\s*nofollow"\s*\/?\s*>/i.test(html)) {
    throw new Error('Expected restricted staging and noindex markers in the build');
  }
  const assets = lstatSync(join(dist, 'assets'));
  if (assets.isSymbolicLink() || !assets.isDirectory()) throw new Error('Missing build assets directory');
  for (const filename of ['sitemap.xml', '.htaccess', 'robots.txt']) {
    const path = join(dist, filename);
    if (lstatSync(path, { throwIfNoEntry: false })) requireRegularFile(path);
  }
  // The inherited sitemap and rewrite target an unrelated/unverified site.
  rmSync(join(dist, 'sitemap.xml'), { force: true });
  rmSync(join(dist, '.htaccess'), { force: true });
  writeFileSync(join(dist, 'robots.txt'), 'User-agent: *\nDisallow: /\n');
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  try {
    if (process.argv.length !== 2) throw new Error('This command accepts no arguments');
    const output = fileURLToPath(new URL('../dist', import.meta.url));
    finalizeStagingBuild(output);
    console.log('Restricted staging build complete. Not a production release; no production sitemap generated.');
  } catch (error) {
    console.error(`Restricted staging build failed: ${error.message}`);
    process.exitCode = 1;
  }
}
