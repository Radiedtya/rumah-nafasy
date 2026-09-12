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
    try {
      await auth.login('rina@example.com', 'password')
    } catch {
      // ignore
    }
  }
})

const pasienNav = [
  { label: 'Overview', href: '/dashboard', icon: HomeIcon },
  { label: 'Sesi Saya', href: '/dashboard/sesi', icon: CalendarDaysIcon },
  { label: 'Cari Psikolog', href: '/dashboard/psikolog', icon: UserGroupIcon },
  { label: 'Profil', href: '/dashboard/profil', icon: UserCircleIcon },
]

const psikologNav = [
  { label: 'Overview', href: '/dashboard', icon: HomeIcon },
  { label: 'Jadwal Praktek', href: '/dashboard/jadwal', icon: ClockIcon },
  { label: 'Konsultasi & Catatan', href: '/dashboard/konsultasi', icon: ClipboardDocumentListIcon },
  { label: 'Profil', href: '/dashboard/profil', icon: UserCircleIcon },
]

const navItems = computed(() => auth.isPsikolog ? psikologNav : pasienNav)

const currentLabel = computed(
  () => navItems.value.find((n) => n.href === route.path)?.label ?? 'Dashboard'
)

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="flex h-screen bg-white dark:bg-neutral-950 font-sans overflow-hidden text-neutral-900 dark:text-neutral-100">
    <!-- Mobile overlay -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-20 bg-black/30 lg:hidden"
      @click="sidebarOpen = false"
    />

    <!-- ===== SIDEBAR ===== -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-30 w-56 bg-white dark:bg-neutral-950 border-r border-neutral-200 dark:border-neutral-800 flex flex-col transition-transform duration-200',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        'lg:relative lg:translate-x-0',
      ]"
    >
      <!-- Brand -->
      <div class="flex items-center justify-between px-4 h-14 border-b border-neutral-200 dark:border-neutral-800 shrink-0">
        <div class="flex items-center gap-2.5">
          <div class="w-7 h-7 rounded bg-neutral-900 dark:bg-white flex items-center justify-center shrink-0">
            <span class="text-white dark:text-neutral-900 font-bold text-[11px] tracking-tight">RN</span>
          </div>
          <div>
            <span class="font-semibold text-sm text-neutral-900 dark:text-neutral-100 tracking-tight block leading-tight">
              Rumah Natasy
            </span>
            <span class="text-[10px] text-neutral-500 dark:text-neutral-400 tracking-wide uppercase font-medium">
              {{ auth.isPsikolog ? 'Psikolog' : 'Pasien' }}
            </span>
          </div>
        </div>
        <button
          type="button"
          class="lg:hidden p-1 text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200"
          @click="sidebarOpen = false"
        >
          <XMarkIcon class="w-4 h-4" />
        </button>
      </div>

      <!-- Nav -->
      <nav class="flex-1 px-2 py-3 overflow-y-auto">
        <ul class="flex flex-col gap-0.5">
          <li v-for="item in navItems" :key="item.href">
            <RouterLink
              :to="item.href"
              class="flex items-center gap-2.5 px-3 py-2 rounded text-sm transition-colors duration-100"
              :class="route.path === item.href
                ? 'bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 font-medium'
                : 'text-neutral-500 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-900 hover:text-neutral-900 dark:hover:text-neutral-100'"
              @click="sidebarOpen = false"
            >
              <component :is="item.icon" class="w-4 h-4 shrink-0" />
              {{ item.label }}
            </RouterLink>
          </li>
        </ul>
      </nav>

      <!-- User footer -->
      <div class="px-2 py-3 border-t border-neutral-200 dark:border-neutral-800 shrink-0 space-y-1">
        <div class="px-3 py-2 rounded bg-neutral-50 dark:bg-neutral-900">
          <p class="text-xs font-medium text-neutral-900 dark:text-neutral-100 truncate leading-tight">
            {{ auth.user?.name || '—' }}
          </p>
          <p class="text-[11px] text-neutral-400 truncate mt-0.5">
            {{ auth.user?.email || '' }}
          </p>
        </div>

        <button
          type="button"
          class="flex items-center gap-2 w-full px-3 py-2 rounded text-sm text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100 hover:bg-neutral-50 dark:hover:bg-neutral-900 transition-colors"
          @click="handleLogout"
        >
          <ArrowLeftOnRectangleIcon class="w-4 h-4" />
          Keluar
        </button>
      </div>
    </aside>

    <!-- ===== MAIN ===== -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Topbar -->
      <header class="flex items-center gap-4 px-5 h-14 bg-white dark:bg-neutral-950 border-b border-neutral-200 dark:border-neutral-800 shrink-0">
        <button
          type="button"
          class="lg:hidden p-1.5 rounded text-neutral-500 hover:bg-neutral-100 dark:hover:bg-neutral-900"
          @click="sidebarOpen = !sidebarOpen"
        >
          <Bars3Icon class="w-5 h-5" />
        </button>

        <span class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
          {{ currentLabel }}
        </span>

        <div class="ml-auto flex items-center gap-3">
          <QuickRoleSwitcher />
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto bg-neutral-50 dark:bg-neutral-950 p-5 md:p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>
