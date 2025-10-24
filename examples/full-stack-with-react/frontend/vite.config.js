import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  base: process.env.YAFS_ENV === 'production' ? '/assets/build/' : '/',
  server: {
    port: 5173,
    host: true,
    cors: true,
    origin: 'http://localhost:5173',
  },
  build: {
    outDir: '../public/assets/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: './index.html',
    },
  },
})
