import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    host: '0.0.0.0',
    proxy: {
      '/api': {
        target: 'https://api.woodex.com.pk',
        changeOrigin: true,
        secure: false
      }
    }
  },
  build: {
    outDir: 'dist'
  }
})