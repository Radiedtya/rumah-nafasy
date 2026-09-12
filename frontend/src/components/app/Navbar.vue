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
  <header class="mx-auto w-[calc(100%-32px)] max-w-[1440px] min-[701px]:w-[calc(100%-48px)]">
    <nav class="flex min-h-[72px] items-center justify-between border-b border-[var(--line)] min-[701px]:min-h-[88px]" aria-label="Navigasi utama">
      <a class="flex items-center gap-2.5 text-xs font-bold uppercase tracking-[.08em]" :href="brand.href" :aria-label="brand.ariaLabel">
        <span class="grid size-[30px] place-items-center bg-[var(--inverse)] text-[10px] tracking-normal text-[var(--inverse-text)]" aria-hidden="true">{{ brand.mark }}</span>
        <span>{{ brand.name }}</span>
      </a>

      <div class="hidden gap-8 text-xs uppercase tracking-[.08em] text-[var(--muted)] min-[701px]:flex" aria-label="Menu utama">
        <a v-for="link in links" :key="link.href" :href="link.href">{{ link.label }}</a>
      </div>

      <div class="flex items-center gap-3 min-[701px]:gap-6">
        <button
          class="inline-flex size-5 items-center justify-center border-0 bg-transparent p-0 text-[var(--text)] focus-visible:outline-2 focus-visible:outline-[var(--text)] focus-visible:outline-offset-4"
          type="button"
          :aria-label="theme === 'light' ? 'Aktifkan dark mode' : 'Aktifkan light mode'"
          :aria-pressed="theme === 'dark'"
          @click="toggleTheme"
        >
          <SunIcon v-if="theme === 'light'" :size="18" aria-hidden="true" />
          <MoonIcon v-else :size="18" aria-hidden="true" />
        </button>
      </div>
    </nav>
  </header>
</template>