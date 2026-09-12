<script setup lang="ts">
import { ref } from 'vue'
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

const closeMobile = () => { mobileOpen.value = false }
</script>

<template>
  <header class="site-header">
    <nav class="site-nav" aria-label="Navigasi utama">
      <!-- Brand -->
      <a class="brand" :href="brand.href" :aria-label="brand.ariaLabel">
        <img src="/icon.svg" alt="" aria-hidden="true" class="block size-10 object-contain" />
        <span>{{ brand.name }}</span>
      </a>

      <!-- Desktop nav links -->
      <div class="nav-links" aria-label="Menu utama">
        <a v-for="link in links" :key="link.href" :href="link.href">{{ link.label }}</a>
      </div>

      <!-- Desktop actions -->
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

      <!-- Burger button — mobile only -->
      <button
        type="button"
        class="mobile-burger"
        :aria-label="mobileOpen ? 'Tutup menu' : 'Buka menu'"
        :aria-expanded="mobileOpen"
        aria-controls="mobile-menu"
        @click="mobileOpen = true"
      >
        <!-- 3 baris burger -->
        <span aria-hidden="true" />
        <span aria-hidden="true" />
        <span aria-hidden="true" />
      </button>
    </nav>
  </header>

  <!-- Mobile menu overlay — full screen, dark bg persis gambar -->
  <Teleport to="body">
    <div
      v-if="mobileOpen"
      id="mobile-menu"
      role="dialog"
      aria-modal="true"
      aria-label="Menu navigasi"
      class="mobile-menu-overlay"
    >
      <!-- Top bar: brand + close -->
      <div class="mobile-menu-topbar">
        <a :href="brand.href" class="mobile-brand" @click="closeMobile">
          <img src="/icon.svg" alt="" aria-hidden="true" class="block size-8 object-contain brightness-200" />
        </a>
        <button
          type="button"
          class="mobile-close"
          aria-label="Tutup menu"
          @click="closeMobile"
        >
          <!-- X icon -->
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M4 4l12 12M16 4L4 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </button>
      </div>

      <!-- Nav links -->
      <nav class="mobile-nav" aria-label="Menu mobile">
        <a
          v-for="link in links"
          :key="link.href"
          :href="link.href"
          class="mobile-nav-item"
          @click="closeMobile"
        >
          {{ link.label }}
        </a>
      </nav>

      <!-- CTA buttons -->
      <div class="mobile-cta">
        <a href="#kontak" class="mobile-cta-btn mobile-cta-outline" @click="closeMobile">Masuk</a>
        <a href="#mulai" class="mobile-cta-btn mobile-cta-primary" @click="closeMobile">Mulai Sekarang</a>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
/* Burger — hanya muncul di mobile */
.mobile-burger {
  display: none;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  width: 36px;
  height: 36px;
  padding: 6px;
  background: transparent;
  border: none;
  cursor: pointer;
  margin-left: auto;
}

.mobile-burger span {
  display: block;
  height: 2px;
  width: 100%;
  border-radius: 2px;
  background: var(--ink);
}

@media (max-width: 760px) {
  .mobile-burger {
    display: flex;
  }

  /* sembunyikan desktop actions di mobile */
  .nav-actions {
    display: none;
  }
}

/* ===== MOBILE MENU OVERLAY ===== */
.mobile-menu-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: #0a0a0a;
  display: flex;
  flex-direction: column;
  padding: 0 24px 40px;
  overflow-y: auto;
}

.mobile-menu-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 64px;
  padding: 0 0 8px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  margin-bottom: 8px;
}

.mobile-brand {
  display: inline-flex;
  align-items: center;
  text-decoration: none;
}

.mobile-close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: transparent;
  border: none;
  cursor: pointer;
  color: #fff;
  opacity: 0.8;
}

.mobile-close:hover {
  opacity: 1;
}

/* Nav links */
.mobile-nav {
  display: flex;
  flex-direction: column;
  flex: 1;
  padding: 16px 0;
}

.mobile-nav-item {
  display: block;
  padding: 18px 0;
  font-size: 1.125rem;
  font-weight: 500;
  color: rgba(255,255,255,0.6);
  text-decoration: none;
  border-bottom: 1px solid rgba(255,255,255,0.07);
  letter-spacing: -0.01em;
  transition: color 150ms ease;
}

.mobile-nav-item:first-child {
  border-top: 1px solid rgba(255,255,255,0.07);
}

.mobile-nav-item:hover {
  color: #fff;
}

/* CTA buttons */
.mobile-cta {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 32px;
}

.mobile-cta-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 52px;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 600;
  text-decoration: none;
  letter-spacing: -0.01em;
  transition: opacity 150ms ease;
}

.mobile-cta-btn:hover {
  opacity: 0.85;
}

.mobile-cta-outline {
  background: #1a1a1a;
  color: #fff;
  border: 1px solid rgba(255,255,255,0.15);
}

.mobile-cta-primary {
  background: #f5f5f5;
  color: #0a0a0a;
  border: none;
}
</style>
