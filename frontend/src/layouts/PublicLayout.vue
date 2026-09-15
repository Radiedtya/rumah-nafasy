<script setup lang="ts">
import { onMounted } from 'vue'
import Footer from '../components/app/Footer.vue'
import Navbar from '../components/app/Navbar.vue'
import { useTheme } from '../composables/useTheme'

const { initializeTheme } = useTheme()

onMounted(initializeTheme)

const navigationLinks: { label: string; href: string }[] = [
  { label: 'Cara Kerja', href: '#cara-kerja' },
  { label: 'Psikolog', href: '#ahli' },
  { label: 'Layanan', href: '#kategori' },
  { label: 'Ulasan', href: '#ulasan' },
  { label: 'FAQ', href: '#faq' },
]

const socialLinks = [
  { label: 'Bluesky', href: 'https://bsky.app', icon: 'bluesky' as const },
  { label: 'Instagram', href: 'https://instagram.com', icon: 'instagram' as const },
  { label: 'TikTok', href: 'https://tiktok.com', icon: 'tiktok' as const },
  { label: 'X', href: 'https://x.com', icon: 'x' as const },
]
</script>

<template>
  <div
    class="app-shell flex min-h-svh flex-col bg-[var(--background)] font-body text-[var(--text)] antialiased [font-synthesis:none] [text-rendering:optimizeLegibility] overflow-x-hidden"
  >
    <Navbar
      :brand="{
        name: 'Rumah Natasy',
        href: '/',
        mark: '',
        ariaLabel: 'Beranda Rumah Natasy',
      }"
      :links="navigationLinks"
    />

    <main class="w-full max-w-[1280px] mx-auto px-4 sm:px-6 flex-1 flex flex-col">
      <RouterView />
    </main>

    <Footer :copyright="`© ${new Date().getFullYear()} Rumah Natasy. Seluruh hak dilindungi.`" :social-links="socialLinks" />
  </div>
</template>
