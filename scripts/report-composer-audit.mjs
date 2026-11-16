#!/usr/bin/env node
// Emit short CI annotations only; never print arbitrary raw output or environment data.
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const safe = (value) => String(value ?? '').replace(/[^a-zA-Z0-9 ._:/@+()-]/g, ' ').slice(0, 250);

export function auditMessages(report) {
  const messages = [];
  for (const [name, entries] of Object.entries(report?.advisories ?? {})) {
    if (!Array.isArray(entries)) continue;
    for (const entry of entries) {
      if (!entry || typeof entry !== 'object') continue;
      messages.push(`Advisory: ${safe(name)} ${safe(entry.advisoryId || entry.cve || '')} ${safe(entry.title || '')}`.trim());
    }
  }
  for (const [name, replacement] of Object.entries(report?.abandoned ?? {})) {
    messages.push(`Abandoned package: ${safe(name)} ${safe(replacement || '')}`.trim());
  }
  return messages.length ? messages : ['Composer audit failed without readable advisory details; inspect the workflow logs.'];
}

if (process.argv[1] && fileURLToPath(import.meta.url) === resolve(process.argv[1])) {
  let report;
  try { report = JSON.parse(readFileSync(process.argv[2], 'utf8')); } catch { report = {}; }
  for (const message of auditMessages(report)) console.log(`::error title=Composer audit::${message}`);
}
