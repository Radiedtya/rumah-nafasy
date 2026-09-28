<script setup lang="ts">
import { ref, watch } from 'vue'
import { useTheme } from '../../composables/useTheme'
import SpecularButton from '../ui/SpecularButton.vue'

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
const navHighlightStates = ref<Record<string, 'entering' | 'active' | 'exiting'>>({})

const setNavHighlight = (href: string, state: 'entering' | 'exiting') => {
  navHighlightStates.value = { ...navHighlightStates.value, [href]: state }
}

const finishNavHighlight = (href: string, event: AnimationEvent) => {
  if (event.animationName === 'nav-highlight-enter') {
    navHighlightStates.value = { ...navHighlightStates.value, [href]: 'active' }
  } else if (event.animationName === 'nav-highlight-exit') {
    const { [href]: _, ...remainingStates } = navHighlightStates.value
    navHighlightStates.value = remainingStates
  }
}

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
  <header class="fixed top-0 right-0 left-0 z-50 w-full px-6 pt-0">
    <div class="mx-auto w-full max-w-[1420px] overflow-hidden rounded-b-[30px] shadow-[0_1px_0_rgba(17,17,17,0.08)]">
      <!-- Announcement strip -->
      <!-- <div class="flex h-[40px] items-center justify-between bg-[#4a0035] px-6 text-white sm:px-7">
        <a href="#mulai" class="inline-flex min-w-0 items-center gap-3 no-underline" aria-label="Mulai konsultasi">
          <span class="hidden text-[20px] font-extrabold leading-none tracking-[-0.08em] text-[#fffbf0] sm:inline">rumah</span>
          <span class="truncate text-[13px] font-medium tracking-[-0.01em] text-[#f7b6f5] sm:text-[15px]">Temani perjalanan kesehatan mental Anda.</span>
        </a>
        <a href="#mulai" class="ml-4 inline-flex shrink-0 items-center gap-2 text-[10px] font-bold uppercase tracking-[0.08em] text-[#f6ed5b] no-underline transition-opacity hover:opacity-75 sm:text-[12px]">
          Mulai sekarang
          <span aria-hidden="true" class="text-[17px] leading-none">→</span>
        </a>
      </div> -->

      <nav
        class="flex h-[58px] items-center gap-2 bg-[var(--surface)] px-6 sm:px-7"
        aria-label="Navigasi utama"
      >
        <!-- Brand -->
        <a
          :href="brand.href"
          :aria-label="brand.ariaLabel"
          class="inline-flex shrink-0 items-center gap-2 pr-7 text-[var(--ink)] no-underline"
        >
          <img src="/icons/64.png" alt="" aria-hidden="true" class="h-6 w-6 object-contain" />
          <span class="text-[19px] font-bold leading-none tracking-[-0.055em]">{{ brand.name }}</span>
        </a>

        <!-- Nav links — desktop, tengah -->
        <div class="hidden items-center gap-1 md:flex" aria-label="Menu utama">
          <a
            v-for="link in links"
            :key="link.href"
            :href="link.href"
            class="nav-highlight px-3.5 py-2 text-[15px] font-normal tracking-[-0.02em] text-[var(--text)] no-underline hover:text-white"
            :class="`nav-highlight--${navHighlightStates[link.href] ?? 'hidden'}`"
            @pointerenter="setNavHighlight(link.href, 'entering')"
            @pointerleave="setNavHighlight(link.href, 'exiting')"
            @animationend="finishNavHighlight(link.href, $event)"
          >{{ link.label }}</a>
        </div>

        <!-- Actions kanan — desktop -->
        <div class="ml-auto hidden shrink-0 items-center gap-2 md:flex">
          <button
            type="button"
            class="inline-flex h-6 w-6 items-center justify-center border-none bg-transparent p-0 text-[var(--muted)] transition-colors hover:text-[var(--text)]"
            :aria-label="theme === 'light' ? 'Aktifkan dark mode' : 'Aktifkan light mode'"
            @click="toggleTheme"
          >
            <SunIcon v-if="theme === 'light'" :size="12" aria-hidden="true" />
            <MoonIcon v-else :size="12" aria-hidden="true" />
          </button>
          <RouterLink to="/dashboard" custom v-slot="{ navigate }">
            <SpecularButton
              size="sm"
              :radius="12"
              tint="#111111"
              :tint-opacity="1"
              :blur="0"
              text-color="#ffffff"
              line-color="#ffffff"
              base-color="#111111"
              :intensity="0.7"
              :shine-size="10"
              :shine-fade="40"
              :thickness="1"
              :speed="0.35"
              follow-mouse
              :proximity="250"
              :auto-animate="false"
              class="!h-[44px] !min-w-[176px] !px-6 !py-0 !text-[14px] !font-semibold tracking-[-0.02em] whitespace-nowrap"
              @click="navigate"
            >
              Masuk Dashboard
            </SpecularButton>
          </RouterLink>
        </div>

        <!-- Burger — mobile only -->
        <button
          type="button"
          class="ml-auto flex h-[34px] w-[34px] flex-col justify-center gap-[5px] rounded-lg border-none bg-transparent p-[7px] md:hidden"
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
    </div>
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
          <img src="/icons/64.png" alt="" aria-hidden="true" class="w-8 h-8 object-contain" />
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

<style scoped>
.nav-highlight {
  position: relative;
  isolation: isolate;
}

.nav-highlight::before {
  position: absolute;
  z-index: -1;
  inset: 0;
  background: #48008c;
  content: '';
}

.nav-highlight--hidden::before {
  clip-path: inset(0 0 0 100%);
}

.nav-highlight--entering::before {
  animation: nav-highlight-enter 280ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.nav-highlight--active::before,
.nav-highlight:focus-visible::before {
  clip-path: inset(0);
}

.nav-highlight--exiting::before {
  animation: nav-highlight-exit 280ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

@keyframes nav-highlight-enter {
  from { clip-path: inset(0 100% 0 0); }
  to { clip-path: inset(0); }
}

@keyframes nav-highlight-exit {
  from { clip-path: inset(0); }
  to { clip-path: inset(0 0 0 100%); }
}

@media (prefers-reduced-motion: reduce) {
  .nav-highlight--entering::before,
  .nav-highlight--exiting::before {
    animation-duration: 1ms;
  }
}
</style>
