import { defineConfig } from 'astro/config';
import react from '@astrojs/react';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.dirname(fileURLToPath(import.meta.url));
// Isolated experiment using sibling Reimagin source; not a replacement storefront.
export default defineConfig({
  integrations: [react()],
  server: { host: '0.0.0.0', port: 5177, allowedHosts: process.env.ARENA_PREVIEW === '1' ? ['.e2b.app'] : [] },
  vite: {
    resolve: {
      alias: { '@': path.resolve(root, '../reimagined-storefront/src') },
      dedupe: ['react', 'react-dom'],
    },
    preview: { allowedHosts: process.env.ARENA_PREVIEW === '1' ? ['.e2b.app'] : [] },
    server: {
      ws: process.env.ARENA_PREVIEW === '1' ? { clientPort: 443 } : undefined,
      proxy: process.env.FURNITURE_PREVIEW_API ? {
        '/storefront-api': { target: process.env.FURNITURE_PREVIEW_API, changeOrigin: true },
      } : undefined,
    },
  },
});
