import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react-swc';
import path from 'node:path';

// Isolated, local-only demo. No reference-repository env files or live API proxy.
export default defineConfig({
  server: {
    host: '0.0.0.0',
    port: 5176,
    strictPort: true,
    allowedHosts: true,
    hmr: process.env.ARENA_PREVIEW === '1' ? { clientPort: 443 } : undefined,
    // Dev-server only: browsers use a same-origin URL; never ship this internal address.
    proxy: process.env.FURNITURE_PREVIEW_API ? {
      '/storefront-api': { target: process.env.FURNITURE_PREVIEW_API, changeOrigin: true },
    } : undefined,
  },
  plugins: [react()],
  resolve: { alias: { '@': path.resolve(__dirname, './src') } },
});
