import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react-swc';
import path from 'node:path';
import { buildPreviewProxy } from './preview-proxy.mjs';

// Preview-only development proxy. Never use a reference-repository env or production credentials.
export default defineConfig(({ mode }) => ({
  // The protected test candidate must never ingest provider keys/URLs from an
  // operator's environment. Non-staging preview builds keep their normal prefix.
  envPrefix: mode === 'furniture-staging' ? 'FURNITURE_STAGING_UNUSED_' : 'VITE_',
  server: {
    host: '0.0.0.0',
    port: 5176,
    strictPort: true,
    allowedHosts: true,
    hmr: process.env.ARENA_PREVIEW === '1' ? { clientPort: 443 } : undefined,
    // Dev-server only: same-origin storefront and Furniture admin in the synthetic preview.
    // No backend address or credentials are exposed to browser bundles. Do not deploy this
    // as a production proxy; Laravel's auth/CSRF/permissions remain authoritative.
    proxy: buildPreviewProxy(process.env.FURNITURE_PREVIEW_API),
  },
  plugins: [
    react(),
    ...(mode === 'furniture-staging' ? [{
      name: 'mark-restricted-furniture-staging',
      transformIndexHtml: () => [{ tag: 'meta', attrs: { name: 'furniture-staging', content: 'restricted' }, injectTo: 'head' as const }],
    }] : []),
  ],
  resolve: { alias: { '@': path.resolve(__dirname, './src') } },
}));
