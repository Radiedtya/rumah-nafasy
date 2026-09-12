/// <reference types="vite-ssg" />
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  base: process.env.NODE_ENV === 'production' ? '/rumah-natasy/' : '/',
  plugins: [vue(), tailwindcss()],
  ssgOptions: {
    // Exclude dashboard & auth routes from SSG — tetap SPA
    includedRoutes(paths: string[]) {
      return paths.filter((path: string) => !path.startsWith('/dashboard') && !path.startsWith('/login') && !path.startsWith('/register'))
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
      '/storage': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
    watch: {
      usePolling: true,
      interval: 1000,
    },
  },
})
