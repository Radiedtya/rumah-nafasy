/// <reference types="vite-ssg" />
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  base: '/',
  plugins: [vue(), tailwindcss()],
  build: {
    outDir: '../.nafasy/dist',
    emptyOutDir: true,
  },
  ssgOptions: {
    // Exclude dashboard & auth routes from SSG — tetap SPA
    includedRoutes(paths: string[]) {
      return paths.filter((path: string) => !path.startsWith('/dashboard') && !path.startsWith('/login') && !path.startsWith('/register'))
    },
  },
  server: {
    port: 3000,
    strictPort: true,

    allowedHosts: [
      'coy-excludable-foolishly.ngrok-free.dev',
    ],  

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
