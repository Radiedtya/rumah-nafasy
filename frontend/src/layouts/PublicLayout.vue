<script setup lang="ts">
import { onMounted } from 'vue'
import Footer from '../components/app/Footer.vue'
import Navbar from '../components/app/Navbar.vue'
import { useTheme } from '../composables/useTheme'

const { initializeTheme } = useTheme()

onMounted(initializeTheme)

const navigationLinks: { label: string; href: string }[] = [
  // { label: '', href: '/page' },
]

const socialLinks = [
  { label: 'Bluesky', href: 'https://bsky.app', icon: 'bluesky' as const },
  { label: 'Instagram', href: 'https://instagram.com', icon: 'instagram' as const },
  { label: 'TikTok', href: 'https://tiktok.com', icon: 'tiktok' as const },
  { label: 'X', href: 'https://x.com', icon: 'x' as const },
]
</script>

<template>
  <div class="flex min-h-svh min-w-80 flex-col bg-[var(--background)] font-sans text-[var(--text)] antialiased [font-synthesis:none] [text-rendering:optimizeLegibility]">
    <Navbar
      :brand="{
        name: '',
        href: '/',
        mark: '',
        ariaLabel: '',
      }"
      :links="navigationLinks"
    />

    <main class="mx-auto flex w-[calc(100%-32px)] max-w-[1440px] flex-1 flex-col min-[701px]:w-[calc(100%-48px)]">
      <RouterView />
      <Footer copyright="© 2026 Rumah Natasy" :social-links="socialLinks" />
    </main>
  </div>
</template>