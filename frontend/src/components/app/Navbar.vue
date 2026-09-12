<script setup lang="ts">
import { ref, watch } from 'vue'
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
const mobileOpen = ref(false)

let savedScrollY = 0
watch(mobileOpen, (open) => {
  if (typeof document === 'undefined') return
  if (open) {
    savedScrollY = window.scrollY
    document.body.style.position = 'fixed'
    document.body.style.top = `-${savedScrollY}px`
    document.body.style.width = '100%'
    document.body.style.overflowY = 'scroll'
  } else {
    document.body.style.position = ''
    document.body.style.top = ''
    document.body.style.width = ''
    document.body.style.overflowY = ''
    window.scrollTo(0, savedScrollY)
  }
})

const closeMobile = () => { mobileOpen.value = false }
</script>

<template>
  <!-- DESKTOP NAVBAR -->
  <header class="sticky top-0 z-50 w-full bg-[color-mix(in_srgb,var(--background)_85%,transparent)] backdrop-blur-md">
    <nav
      class="flex items-center h-[72px] w-full max-w-[1200px] mx-auto px-6 gap-2"
      aria-label="Navigasi utama"
    >
      <!-- Brand -->
      <a
        :href="brand.href"
        :aria-label="brand.ariaLabel"
        class="inline-flex items-center gap-2 shrink-0 mr-8 text-[var(--ink)] no-underline font-bold text-[17px] tracking-tight"
      >
        <img src="/icon.svg" alt="" aria-hidden="true" class="w-7 h-7 object-contain" />
        <span>{{ brand.name }}</span>
      </a>

      <!-- Nav links — desktop, tengah -->
      <div class="hidden md:flex items-center gap-0.5" aria-label="Menu utama">
        <a
          v-for="link in links"
          :key="link.href"
          :href="link.href"
          class="px-3.5 py-1.5 rounded-lg text-[14px] font-light text-[var(--muted)] no-underline tracking-wide hover:text-[var(--ink)] hover:bg-[color-mix(in_srgb,var(--ink)_6%,transparent)] transition-colors duration-150"
        >{{ link.label }}</a>
      </div>

      <!-- Actions kanan — desktop -->
      <div class="hidden md:flex items-center gap-2 ml-auto shrink-0">
        <a
          href="#mulai"
          class="inline-flex items-center h-[36px] px-4 rounded-lg bg-[var(--ink)] text-[var(--inverse-text)] text-[14px] font-semibold no-underline tracking-tight hover:opacity-80 transition-opacity duration-150"
        >
          Mulai
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true" class="ml-1.5">
            <path d="M2.5 7h9M7.5 3l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>
      </div>

      <!-- Burger — mobile only -->
      <button
        type="button"
        class="md:hidden ml-auto flex flex-col justify-center gap-[5px] w-[34px] h-[34px] p-[7px] bg-transparent border-none rounded-lg cursor-pointer"
        :aria-label="mobileOpen ? 'Tutup menu' : 'Buka menu'"
        :aria-expanded="mobileOpen"
        aria-controls="mobile-menu"
        @click="mobileOpen = true"
      >
        <span class="block h-[1.5px] w-full rounded-sm bg-[var(--ink)]" aria-hidden="true" />
        <span class="block h-[1.5px] w-full rounded-sm bg-[var(--ink)]" aria-hidden="true" />
        <span class="block h-[1.5px] w-full rounded-sm bg-[var(--ink)]" aria-hidden="true" />
      </button>
    </nav>
  </header>

  <!-- MOBILE MENU OVERLAY -->
  <Teleport to="body">
    <div
      v-if="mobileOpen"
      id="mobile-menu"
      role="dialog"
      aria-modal="true"
      aria-label="Menu navigasi"
      class="fixed inset-0 z-[9999] bg-[var(--background)] flex flex-col px-5 overflow-hidden"
    >
      <!-- Topbar -->
      <div class="flex items-center justify-between h-[60px] border-b border-[var(--line)] shrink-0">
        <a :href="brand.href" class="inline-flex items-center no-underline" @click="closeMobile">
          <img src="/icon.svg" alt="" aria-hidden="true" class="w-8 h-8 object-contain" />
        </a>
        <button
          type="button"
          class="inline-flex items-center justify-center w-8 h-8 bg-transparent border-none cursor-pointer text-[var(--muted)] hover:text-[var(--text)] transition-colors"
          aria-label="Tutup menu"
          @click="closeMobile"
        >
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M4 4l12 12M16 4L4 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </button>
      </div>

      <!-- Nav links — scrollable -->
      <nav class="flex flex-col flex-1 overflow-y-auto" aria-label="Menu mobile">
        <a
          v-for="link in links"
          :key="link.href"
          :href="link.href"
          class="block py-[17px] text-[17px] font-normal text-[var(--muted)] no-underline border-b border-[var(--line)] tracking-tight hover:text-[var(--text)] transition-colors duration-150 first:border-t first:border-[var(--line)]"
          @click="closeMobile"
        >{{ link.label }}</a>
      </nav>

      <!-- Footer: theme switch + CTA -->
      <div class="shrink-0 flex flex-col gap-3.5 pt-6 pb-8 border-t border-[var(--line)]">
        <!-- Theme toggle row -->
        <div class="flex items-center justify-between">
          <span class="inline-flex items-center gap-2 text-[14px] font-normal text-[var(--muted)]">
            <SunIcon v-if="theme === 'light'" :size="15" aria-hidden="true" />
            <MoonIcon v-else :size="15" aria-hidden="true" />
            {{ theme === 'light' ? 'Mode Terang' : 'Mode Gelap' }}
          </span>

          <!-- Switch -->
          <button
            type="button"
            role="switch"
            :aria-checked="theme === 'dark'"
            :aria-label="theme === 'light' ? 'Aktifkan dark mode' : 'Aktifkan light mode'"
            class="relative w-11 h-[26px] rounded-full border-none cursor-pointer shrink-0 transition-colors duration-200"
            :class="theme === 'dark' ? 'bg-[var(--ink)]' : 'bg-[var(--line)]'"
            @click="toggleTheme"
          >
            <span
              class="absolute top-[3px] left-[3px] w-[18px] h-[18px] rounded-full bg-[var(--background)] shadow-sm transition-transform duration-200"
              :class="theme === 'dark' ? 'translate-x-[18px]' : 'translate-x-0'"
            />
          </button>
        </div>

        <!-- CTA -->
        <a
          href="#mulai"
          class="flex items-center justify-center h-[50px] rounded-xl bg-[var(--ink)] text-[var(--inverse-text)] text-[15px] font-semibold no-underline tracking-tight hover:opacity-80 transition-opacity duration-150"
          @click="closeMobile"
        >Mulai Sekarang</a>
      </div>
    </div>
  </Teleport>
</template>
