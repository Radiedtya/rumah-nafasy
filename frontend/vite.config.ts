import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  base: process.env.NODE_ENV === 'production' ? '/rumah-natasy/' : '/',
  plugins: [vue(), tailwindcss()],
  ssgOptions: {
    // Exclude dashboard routes from SSG — tetap SPA
    includedRoutes(paths) {
      return paths.filter((path) => !path.startsWith('/dashboard'))
    },
  },
  server: {
    watch: {
      usePolling: true,
      interval: 1000,
    },
  },
})
