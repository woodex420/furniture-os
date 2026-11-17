// Development-only same-origin preview routing. This is not a production gateway.
export const previewProxyPatterns = [
  '^/storefront-api(?:/|\\?|$)',
  '^/admin(?:/|\\?|$)',
  '^/login(?:/|\\?|$)',
  '^/logout(?:\\?|$)',
  '^/build(?:/|\\?|$)',
  '^/workspace/livewire(?:/|\\?|$)',
  '^/livewire(?:/|\\?|$)',
  '^/livewire-[a-f0-9]{8}(?:/|\\?|$)',
];

export function buildPreviewProxy(target) {
  if (!target) return undefined;
  return Object.fromEntries(previewProxyPatterns.map((prefix) => [prefix, { target, changeOrigin: true }]));
}
