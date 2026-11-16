import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'node:path';
export default defineConfig({ root: resolve('apps/dashboard'), plugins: [react()], server: { host: '0.0.0.0', allowedHosts: true, port: 5173 }, build: { outDir: 'dist', emptyOutDir: true } });
