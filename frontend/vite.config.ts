/// <reference types="vite-ssg" />
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  base: '/',
  plugins: [vue(), tailwindcss()],
  ssgOptions: {
    // Exclude dashboard & auth routes from SSG — tetap SPA
    includedRoutes(paths: string[]) {
      return paths.filter((path: string) => !path.startsWith('/dashboard') && !path.startsWith('/login') && !path.startsWith('/register'))
    },
  },
    server: {
    port: 5173,

    allowedHosts: [
      'coy-excludable-foolishly.ngrok-free.dev',
    ],

    proxy: {
      '/api': {
        target: 'https://coy-excludable-foolishly.ngrok-free.dev',
        changeOrigin: true,
      },

      '/storage': {
        target: 'https://coy-excludable-foolishly.ngrok-free.dev',
        changeOrigin: true,
      },
    },

    watch: {
      usePolling: true,
      interval: 1000,
    },
  },
})
