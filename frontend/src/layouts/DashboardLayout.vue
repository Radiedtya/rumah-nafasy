<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  HomeIcon,
  CalendarDaysIcon,
  UserGroupIcon,
  UserCircleIcon,
  ClockIcon,
  ClipboardDocumentListIcon,
  ArrowLeftOnRectangleIcon,
  Bars3Icon,
  XMarkIcon,
  BellIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '../stores/auth'
import QuickRoleSwitcher from '../components/ui/QuickRoleSwitcher.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const sidebarOpen = ref(false)

onMounted(async () => {
  if (auth.token && !auth.user) {
    await auth.fetchMe()
  } else if (!auth.token) {
    // Default auto-login as first seeded pasien for convenience if not logged in
    try {
      await auth.login('rina@example.com', 'password')
    } catch {
      // ignore
    }
  }
})

const pasienNav = [
  { label: 'Beranda', href: '/dashboard', icon: HomeIcon },
  { label: 'Sesi Saya', href: '/dashboard/sesi', icon: CalendarDaysIcon },
  { label: 'Cari Psikolog', href: '/dashboard/psikolog', icon: UserGroupIcon },
  { label: 'Profil Saya', href: '/dashboard/profil', icon: UserCircleIcon },
]

const psikologNav = [
  { label: 'Overview', href: '/dashboard', icon: HomeIcon },
  { label: 'Jadwal Praktek', href: '/dashboard/jadwal', icon: ClockIcon },
  { label: 'Konsultasi & Catatan', href: '/dashboard/konsultasi', icon: ClipboardDocumentListIcon },
  { label: 'Profil & Tarif', href: '/dashboard/profil', icon: UserCircleIcon },
]

const navItems = computed(() => {
  return auth.isPsikolog ? psikologNav : pasienNav
})

const currentLabel = computed(
  () => navItems.value.find((n) => n.href === route.path)?.label ?? 'Dashboard'
)

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="flex h-screen bg-neutral-50 dark:bg-neutral-950 font-body overflow-hidden text-neutral-900 dark:text-neutral-100">
    <!-- Overlay mobile -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-20 bg-black/40 lg:hidden"
      aria-hidden="true"
      @click="sidebarOpen = false"
    />

    <!-- ===== SIDEBAR ===== -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-30 w-64 bg-white dark:bg-neutral-900 border-r border-neutral-200 dark:border-neutral-800 flex flex-col transition-transform duration-300',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        'lg:relative lg:translate-x-0',
      ]"
      aria-label="Sidebar navigasi dashboard"
    >
      <!-- Brand -->
      <div class="flex items-center gap-3 px-6 h-16 border-b border-neutral-200 dark:border-neutral-800 shrink-0">
        <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-rose-500 to-rose-400 flex items-center justify-center text-white font-bold text-sm shadow-sm">
          RN
        </div>
        <div class="min-w-0">
          <span class="font-display font-semibold text-base text-neutral-900 dark:text-neutral-100 tracking-tight block">
            Rumah Natasy
          </span>
          <span class="text-[10px] text-rose-500 font-semibold uppercase tracking-wider block">
            {{ auth.isPsikolog ? 'Portal Psikolog' : 'Portal Pasien' }}
          </span>
        </div>
        <button
          type="button"
          class="ml-auto lg:hidden p-1 rounded-lg text-neutral-500 hover:bg-neutral-100 dark:hover:bg-neutral-800"
          aria-label="Tutup menu"
          @click="sidebarOpen = false"
        >
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Nav -->
      <nav class="flex-1 px-3 py-4 overflow-y-auto" aria-label="Menu dashboard">
        <ul class="flex flex-col gap-1" role="list">
          <li v-for="item in navItems" :key="item.href">
            <RouterLink
              :to="item.href"
              class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150"
              :class="route.path === item.href
                ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 font-semibold shadow-xs'
                : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800 hover:text-neutral-900 dark:hover:text-neutral-100'"
              :aria-current="route.path === item.href ? 'page' : undefined"
              @click="sidebarOpen = false"
            >
              <component :is="item.icon" class="w-5 h-5 shrink-0" aria-hidden="true" />
              {{ item.label }}
            </RouterLink>
          </li>
        </ul>
      </nav>

      <!-- User footer -->
      <div class="px-4 py-4 border-t border-neutral-200 dark:border-neutral-800 shrink-0">
        <div class="flex items-center gap-3 mb-3 p-2 rounded-xl bg-neutral-50 dark:bg-neutral-800/50">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-rose-400 to-orange-400 shrink-0 flex items-center justify-center text-sm font-bold text-white shadow-xs">
            {{ auth.user?.name ? auth.user.name.charAt(0).toUpperCase() : 'U' }}
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold text-neutral-900 dark:text-neutral-100 truncate">
              {{ auth.user?.name || 'Memuat user...' }}
            </p>
            <p class="text-[10px] text-neutral-500 dark:text-neutral-400 truncate">
              {{ auth.user?.email || '' }}
            </p>
          </div>
        </div>

        <div class="flex items-center justify-between gap-2">
          <RouterLink
            to="/"
            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs text-neutral-500 hover:bg-neutral-100 dark:hover:bg-neutral-800 hover:text-neutral-800 transition-colors"
          >
            ← Beranda Publik
          </RouterLink>

          <button
            type="button"
            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 font-medium transition-colors"
            @click="handleLogout"
          >
            <ArrowLeftOnRectangleIcon class="w-3.5 h-3.5" />
            Keluar
          </button>
        </div>
      </div>
    </aside>

    <!-- ===== MAIN AREA ===== -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Topbar -->
      <header class="flex items-center gap-4 px-4 md:px-6 h-16 bg-white dark:bg-neutral-900 border-b border-neutral-200 dark:border-neutral-800 shrink-0">
        <button
          type="button"
          class="lg:hidden p-2 rounded-lg text-neutral-500 hover:bg-neutral-100 dark:hover:bg-neutral-800"
          :aria-label="sidebarOpen ? 'Tutup menu' : 'Buka menu'"
          :aria-expanded="sidebarOpen"
          @click="sidebarOpen = !sidebarOpen"
        >
          <Bars3Icon class="w-5 h-5" />
        </button>

        <div>
          <h1 class="font-display font-semibold text-neutral-900 dark:text-neutral-100 text-base md:text-lg">
            {{ currentLabel }}
          </h1>
        </div>

        <div class="ml-auto flex items-center gap-3">
          <!-- Role Switcher -->
          <QuickRoleSwitcher />

          <!-- Notifikasi -->
          <button
            type="button"
            class="relative p-2 rounded-xl text-neutral-500 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors"
            aria-label="Notifikasi"
          >
            <BellIcon class="w-5 h-5" />
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full" />
          </button>
        </div>
      </header>

      <!-- Konten halaman -->
      <main class="flex-1 overflow-y-auto bg-neutral-50 dark:bg-neutral-950 p-4 md:p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>
