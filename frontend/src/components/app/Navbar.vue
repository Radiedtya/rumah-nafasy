<script setup lang="ts">
import { useTheme } from '../../composables/useTheme'

type NavigationLink = {
  label: string
  href: string
}

defineProps<{
  brand: {
    name: string
    href: string
    mark: string
    ariaLabel: string
  }
  links: NavigationLink[]
}>()

const { theme, toggleTheme } = useTheme()
</script>

<template>
  <header class="site-header">
    <nav class="site-nav" aria-label="Navigasi utama">
      <a class="brand font-display" :href="brand.href" :aria-label="brand.ariaLabel">
        <span class="brand-mark" aria-hidden="true"><span></span><span></span></span>
        <span>{{ brand.name }}</span>
      </a>

      <div class="nav-links font-display" aria-label="Menu utama">
        <a v-for="link in links" :key="link.href" :href="link.href">{{ link.label }}</a>
      </div>

      <div class="nav-actions">
        <button
          class="theme-toggle"
          type="button"
          :aria-label="theme === 'light' ? 'Aktifkan dark mode' : 'Aktifkan light mode'"
          :aria-pressed="theme === 'dark'"
          @click="toggleTheme"
        >
          <SunIcon v-if="theme === 'light'" :size="18" aria-hidden="true" />
          <MoonIcon v-else :size="18" aria-hidden="true" />
        </button>
        <a class="login-link" href="#kontak">Masuk</a>
        <a class="button button-small" href="#mulai">Mulai</a>
      </div>
    </nav>
  </header>
</template>