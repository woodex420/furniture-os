import test from 'node:test';
import assert from 'node:assert/strict';
import { auditMessages } from './report-composer-audit.mjs';

test('reports useful advisory and abandoned names without workflow command injection', () => {
  const messages = auditMessages({
    advisories: { 'vendor/package': [{ advisoryId: 'GHSA-test', title: 'Unsafe\n::warning::injection' }] },
    abandoned: { 'old/package': 'new/package' },
  });
  assert.equal(messages.length, 2);
  assert.ok(messages[0].includes('vendor/package GHSA-test'));
  assert.ok(!messages[0].includes('\n'));
  assert.ok(messages[1].includes('old/package new/package'));
});

test('gives a generic error when the audit response cannot be read', () => {
  assert.match(auditMessages({})[0], /without readable advisory details/);
});
