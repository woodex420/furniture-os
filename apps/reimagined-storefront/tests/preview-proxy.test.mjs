import test from 'node:test';
import assert from 'node:assert/strict';
import { buildPreviewProxy, previewProxyPatterns } from '../preview-proxy.mjs';

const matches = (path) => previewProxyPatterns.some((pattern) => new RegExp(pattern).test(path));

test('preview proxy is opt-in and keeps browser-facing routes same-origin', () => {
  assert.equal(buildPreviewProxy(undefined), undefined);
  assert.equal(buildPreviewProxy(''), undefined);
  const proxy = buildPreviewProxy('http://internal.example:5176');
  assert.equal(Object.keys(proxy).length, previewProxyPatterns.length);
  for (const config of Object.values(proxy)) assert.deepEqual(config, { target: 'http://internal.example:5176', changeOrigin: true });
  for (const route of ['/admin', '/admin?x=1', '/admin/website/blog', '/login', '/login?next=1', '/logout', '/build/assets/auth.css', '/workspace/livewire/update', '/livewire/livewire.js?id=1', '/livewire-53ac0bbe/livewire.min.js?id=1', '/storefront-api/blog']) assert.equal(matches(route), true, route);
});

test('does not intercept storefront or similarly named routes', () => {
  for (const route of ['/', '/blog', '/administrator', '/login-extra', '/logout-anything', '/building', '/workspace/livewires', '/livewirefoo', '/livewire-53ac0bbe-extra', '/livewire-123/path', '/storefront-api-other']) assert.equal(matches(route), false, route);
});
