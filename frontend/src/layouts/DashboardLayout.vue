<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import {
  HomeIcon,
  CalendarDaysIcon,
  UserGroupIcon,
  UserCircleIcon,
  ArrowLeftOnRectangleIcon,
  Bars3Icon,
  XMarkIcon,
  BellIcon,
  MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline'
import { useTheme } from '../composables/useTheme'

const route = useRoute()
const { theme, toggleTheme } = useTheme()
const sidebarOpen = ref(false)

const navItems = [
  { label: 'Beranda', href: '/dashboard', icon: HomeIcon },
  { label: 'Sesi Saya', href: '/dashboard/sesi', icon: CalendarDaysIcon },
  { label: 'Psikolog', href: '/dashboard/psikolog', icon: UserGroupIcon },
  { label: 'Profil', href: '/dashboard/profil', icon: UserCircleIcon },
]

const currentLabel = computed(
  () => navItems.find((n) => n.href === route.path)?.label ?? 'Dashboard'
)
</script>

<template>
  <div class="flex h-screen bg-neutral-50 dark:bg-neutral-950 font-body overflow-hidden" style="color-scheme: light;">
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
        'fixed inset-y-0 left-0 z-30 w-64 bg-white border-r border-neutral-200 flex flex-col transition-transform duration-300',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        'lg:relative lg:translate-x-0',
      ]"
      aria-label="Sidebar navigasi dashboard"
    >
      <!-- Brand -->
      <div class="flex items-center gap-3 px-6 h-16 border-b border-neutral-200 shrink-0">
        <img src="/icon.svg" alt="" aria-hidden="true" class="w-8 h-8 object-contain" />
        <span class="font-display font-semibold text-base text-neutral-900 tracking-tight">Rumah Natasy</span>
        <button
          type="button"
          class="ml-auto lg:hidden p-1 rounded-lg text-neutral-500 hover:bg-neutral-100"
          aria-label="Tutup menu"
          @click="sidebarOpen = false"
        >
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Nav -->
      <nav class="flex-1 px-3 py-4 overflow-y-auto" aria-label="Menu dashboard">
        <ul class="flex flex-col gap-0.5" role="list">
          <li v-for="item in navItems" :key="item.href">
            <RouterLink
              :to="item.href"
              class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150"
              :class="route.path === item.href
                ? 'bg-rose-50 text-rose-600 font-semibold'
                : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900'"
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
      <div class="px-4 py-4 border-t border-neutral-200 shrink-0">
        <div class="flex items-center gap-3 mb-3 p-2 rounded-xl hover:bg-neutral-50 cursor-pointer group">
          <div class="w-8 h-8 rounded-full bg-gradient-to-br from-rose-300 to-orange-200 shrink-0 flex items-center justify-center text-sm font-bold text-white">
            A
          </div>
          <div class="min-w-0">
            <p class="text-xs font-semibold text-neutral-900 truncate">Anisa Rahmawati</p>
            <p class="text-[10px] text-neutral-500 truncate">anisa@email.com</p>
          </div>
        </div>
        <a
          href="/"
          class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs text-neutral-500 hover:bg-neutral-100 hover:text-neutral-800 transition-colors"
        >
          <ArrowLeftOnRectangleIcon class="w-4 h-4 shrink-0" aria-hidden="true" />
          Kembali ke situs
        </a>
      </div>
    </aside>

    <!-- ===== MAIN AREA ===== -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Topbar -->
      <header class="flex items-center gap-4 px-4 md:px-6 h-16 bg-white border-b border-neutral-200 shrink-0">
        <button
          type="button"
          class="lg:hidden p-2 rounded-lg text-neutral-500 hover:bg-neutral-100"
          :aria-label="sidebarOpen ? 'Tutup menu' : 'Buka menu'"
          :aria-expanded="sidebarOpen"
          @click="sidebarOpen = !sidebarOpen"
        >
          <Bars3Icon class="w-5 h-5" />
        </button>

        <h1 class="font-display font-semibold text-neutral-900 text-base md:text-lg">{{ currentLabel }}</h1>

        <div class="ml-auto flex items-center gap-2">
          <!-- Search -->
          <div class="hidden sm:flex items-center gap-2 bg-neutral-100 rounded-xl px-3 py-2 text-sm text-neutral-500">
            <MagnifyingGlassIcon class="w-4 h-4" aria-hidden="true" />
            <span class="text-xs">Cari...</span>
          </div>

          <!-- Notifikasi -->
          <button
            type="button"
            class="relative p-2 rounded-xl text-neutral-500 hover:bg-neutral-100 transition-colors"
            aria-label="Notifikasi"
          >
            <BellIcon class="w-5 h-5" />
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full" aria-label="Ada notifikasi baru" />
          </button>

          <!-- Avatar -->
          <div class="w-8 h-8 rounded-full bg-gradient-to-br from-rose-300 to-orange-200 flex items-center justify-center text-sm font-bold text-white cursor-pointer">
            A
          </div>
        </div>
      </header>

      <!-- Konten halaman -->
      <main class="flex-1 overflow-y-auto bg-neutral-50 p-4 md:p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>
