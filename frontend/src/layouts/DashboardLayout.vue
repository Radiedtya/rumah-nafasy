<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  DialogRoot,
  DialogPortal,
  DialogOverlay,
  DialogContent,
  DialogTitle,
} from 'reka-ui'
import {
  Bars3Icon,
  XMarkIcon,
  MoonIcon,
  SunIcon,
} from '@heroicons/vue/24/outline'
import SidebarPanelIcon from '../components/icons/SidebarPanelIcon.vue'
import { useTheme } from '../composables/theme'
import { useAuthStore } from '../stores/auth'
import SidebarNav from '../components/dashboard/SidebarNav.vue'
import UserMenu from '../components/dashboard/UserMenu.vue'

const route = useRoute()
const auth = useAuthStore()

/* ---------- Sidebar state (persisted) ---------- */
const COLLAPSE_KEY = 'rn.sidebar.collapsed'

const collapsed = ref(
  typeof window !== 'undefined' ? localStorage.getItem(COLLAPSE_KEY) === '1' : false,
)

function toggleCollapse() {
  collapsed.value = !collapsed.value
  localStorage.setItem(COLLAPSE_KEY, collapsed.value ? '1' : '0')
}

/* ---------- Mobile drawer ---------- */
const drawerOpen = ref(false)
watch(
  () => route.path,
  () => {
    drawerOpen.value = false
  },
)

/* ---------- Theme (composable bersama) ---------- */
const { resolvedMode, setMode } = useTheme()

/* ---------- Auth bootstrap ---------- */
onMounted(async () => {
  // Sesi valid → refresh profil dari server. Tidak ada auto-login dev.
  if (auth.token) {
    await auth.fetchMe()
  }
})

const currentLabel = computed(() => {
  const match = [
    { href: '/dashboard/sesi', label: 'Sesi Saya' },
    { href: '/dashboard/psikolog', label: 'Cari Psikolog' },
    { prefix: '/dashboard/booking/', label: 'Booking Konsultasi' },
    { href: '/dashboard/profil', label: 'Profil' },
    { href: '/dashboard/jadwal', label: 'Jadwal Praktek' },
    { href: '/dashboard/konsultasi', label: 'Konsultasi & Catatan' },
    { href: '/dashboard', label: 'Overview' },
  ].find((n: any) => 'prefix' in n ? route.path.startsWith(n.prefix) : route.path === n.href)
  return match?.label ?? 'Overview'
})

const currentSection = computed(() =>
  route.path === '/dashboard' ? 'Dashboard' : 'Dashboard',
)
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-[var(--background)] text-[var(--text)]">
    <!-- ================= DESKTOP SIDEBAR (collapsible, theme-aware) ================= -->
    <aside
      class="hidden shrink-0 flex-col border-r bg-[var(--sidebar-bg)] transition-[width] duration-200 ease-in-out lg:flex"
      :class="collapsed ? 'w-16' : 'w-60'"
      :style="{ borderColor: 'var(--sidebar-border)' }"
    >
      <!-- Brand + toggle -->
        <div
          class="flex h-14 shrink-0 items-center gap-2.5 border-b px-3"
          :class="collapsed ? 'justify-center' : ''"
          :style="{ borderColor: 'var(--sidebar-border)' }"
        >
          <img
            src="/favicon.svg"
            alt="Logo Rumah Nafasy"
            class="h-8 w-8 shrink-0 rounded-lg object-contain"
          />
          <div v-if="!collapsed" class="min-w-0 flex-1">
            <span class="block truncate text-[13px] font-semibold leading-tight text-[var(--sidebar-text-active)]">
              Rumah Nafasy
            </span>
            <span class="block text-[10px] font-medium uppercase tracking-wider text-[var(--sidebar-text)]">
              {{ auth.isPsikolog ? 'Portal Psikolog' : 'Portal Pasien' }}
            </span>
          </div>
        </div>

      <!-- Nav -->
      <SidebarNav :collapsed="collapsed" />

      <!-- Footer: user menu nempel dasar -->
      <div class="shrink-0 border-t" :style="{ borderColor: 'var(--sidebar-border)' }">
        <div class="p-3" :class="collapsed ? 'flex justify-center' : ''">
          <UserMenu :compact="collapsed" />
        </div>
      </div>
    </aside>

    <!-- ================= MOBILE DRAWER ================= -->
    <DialogRoot v-model:open="drawerOpen">
      <DialogPortal>
        <DialogOverlay class="dialog-overlay fixed inset-0 z-40 bg-black/50 backdrop-blur-[2px] lg:hidden" />
        <DialogContent
          class="dialog-overlay fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-[var(--sidebar-bg)] shadow-2xl focus:outline-none lg:hidden"
          :style="{ animation: 'drawer-in 200ms cubic-bezier(0.16, 1, 0.3, 1)' }"
        >
          <DialogTitle class="sr-only">Menu navigasi</DialogTitle>
          <div
            class="flex h-14 shrink-0 items-center gap-2.5 border-b px-3"
            :style="{ borderColor: 'var(--sidebar-border)' }"
          >
            <img
              src="/favicon.svg"
              alt="Logo Rumah Nafasy"
              class="h-8 w-8 shrink-0 rounded-lg object-contain"
            />
            <div class="min-w-0 flex-1">
              <span class="block truncate text-[13px] font-semibold leading-tight text-[var(--sidebar-text-active)]">
                Rumah Nafasy
              </span>
              <span class="block text-[10px] font-medium uppercase tracking-wider text-[var(--sidebar-text)]">
                {{ auth.isPsikolog ? 'Portal Psikolog' : 'Portal Pasien' }}
              </span>
            </div>
            <button
              type="button"
              class="rounded-lg p-1.5 text-[var(--sidebar-text)] transition-colors hover:bg-[var(--sidebar-hover)] hover:text-[var(--sidebar-text-active)]"
              aria-label="Tutup menu"
              @click="drawerOpen = false"
            >
              <XMarkIcon class="h-4.5 w-4.5" />
            </button>
          </div>

          <SidebarNav :collapsed="false" />

          <div class="shrink-0 border-t p-3" :style="{ borderColor: 'var(--sidebar-border)' }">
            <UserMenu :compact="false" />
          </div>
        </DialogContent>
      </DialogPortal>
    </DialogRoot>

    <!-- ================= MAIN ================= -->
    <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
      <!-- Topbar -->
      <header
        class="flex h-14 shrink-0 items-center gap-3 border-b border-[var(--line)] bg-[var(--surface)] px-4"
      >
        <!-- Toggle sidebar (desktop, kiri header) -->
        <button
          type="button"
          class="hidden h-8.5 w-8.5 items-center justify-center rounded-lg text-[var(--muted)] transition-colors hover:bg-[var(--muted)]/10 hover:text-[var(--text)] focus-visible:ring-2 focus-visible:ring-[var(--accent)]/30 focus-visible:outline-none lg:flex"
          :aria-label="collapsed ? 'Buka sidebar' : 'Tutup sidebar'"
          :title="collapsed ? 'Buka sidebar' : 'Tutup sidebar'"
          @click="toggleCollapse"
        >
          <SidebarPanelIcon class="h-4.5 w-4.5" />
        </button>

        <!-- Hamburger drawer (mobile) -->
        <button
          type="button"
          class="rounded-lg p-2 text-[var(--muted)] transition-colors hover:bg-[var(--muted)]/10 hover:text-[var(--text)] lg:hidden"
          aria-label="Buka menu"
          @click="drawerOpen = true"
        >
          <Bars3Icon class="h-5 w-5" />
        </button>

        <!-- Breadcrumb -->
        <nav class="flex min-w-0 items-center gap-1.5 text-xs">
          <span class="text-[var(--muted)]">{{ currentSection }}</span>
          <span class="text-[var(--muted)]">/</span>
          <span class="truncate font-medium text-[var(--text)]">{{ currentLabel }}</span>
        </nav>

        <div class="ml-auto flex items-center gap-2">
          <!-- Theme toggle (kanan atas) -->
          <button
            type="button"
            class="flex h-8.5 w-8.5 items-center justify-center rounded-lg text-[var(--muted)] transition-colors hover:bg-[var(--muted)]/10 hover:text-[var(--text)] focus-visible:ring-2 focus-visible:ring-[var(--accent)]/30 focus-visible:outline-none"
            :title="resolvedMode === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
            :aria-label="resolvedMode === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
            @click="setMode(resolvedMode === 'dark' ? 'light' : 'dark')"
          >
            <SunIcon v-if="resolvedMode === 'dark'" class="h-4.5 w-4.5" />
            <MoonIcon v-else class="h-4.5 w-4.5" />
          </button>

        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto px-4 py-6 md:px-8">
        <div class="mx-auto w-full max-w-6xl">
          <RouterView />
        </div>
      </main>
    </div>
  </div>
</template>
