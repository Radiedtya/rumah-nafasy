<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router'
import { TooltipRoot, TooltipProvider, TooltipTrigger, TooltipContent } from 'reka-ui'
import {
  Squares2X2Icon,
  CalendarDaysIcon,
  UserGroupIcon,
  ClockIcon,
  ClipboardDocumentListIcon,
  UserCircleIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '../../stores/auth'
import { computed, type Component } from 'vue'

interface Props {
  collapsed: boolean
}

defineProps<Props>()
const route = useRoute()
const auth = useAuthStore()

interface NavItem {
  label: string
  href: string
  icon: Component
}

const pasienNav: NavItem[] = [
  { label: 'Overview', href: '/dashboard', icon: Squares2X2Icon },
  { label: 'Sesi Saya', href: '/dashboard/sesi', icon: CalendarDaysIcon },
  { label: 'Cari Psikolog', href: '/dashboard/psikolog', icon: UserGroupIcon },
  { label: 'Profil', href: '/dashboard/profil', icon: UserCircleIcon },
]

const psikologNav: NavItem[] = [
  { label: 'Overview', href: '/dashboard', icon: Squares2X2Icon },
  { label: 'Jadwal Praktek', href: '/dashboard/jadwal', icon: ClockIcon },
  { label: 'Konsultasi & Catatan', href: '/dashboard/konsultasi', icon: ClipboardDocumentListIcon },
  { label: 'Profil', href: '/dashboard/profil', icon: UserCircleIcon },
]

const navItems = computed<NavItem[]>(() => (auth.isPsikolog ? psikologNav : pasienNav))
</script>

<template>
  <TooltipProvider :delay-duration="200">
    <nav class="flex flex-1 flex-col gap-0.5 overflow-y-auto overflow-x-hidden px-3 py-3">
      <TooltipRoot v-for="item in navItems" :key="item.href">
        <TooltipTrigger as-child>
          <RouterLink
            :to="item.href"
            class="group relative flex h-9 items-center gap-2.5 rounded-lg px-2.5 text-xs font-medium outline-none transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-[var(--accent)]/30"
            :class="
              route.path === item.href
                ? 'bg-[var(--sidebar-hover-active)] text-[var(--sidebar-text-active)]'
                : 'text-[var(--sidebar-text)] hover:bg-[var(--sidebar-hover)] hover:text-[var(--sidebar-text-active)]'
            "
          >
            <component :is="item.icon" class="h-4.5 w-4.5 shrink-0" />
            <span class="nav-label truncate">{{ item.label }}</span>
            <span
              v-if="route.path === item.href"
              class="absolute -left-3 top-1/2 h-5 w-0.5 -translate-y-1/2 rounded-full bg-[var(--accent)]"
            />
          </RouterLink>
        </TooltipTrigger>
        <TooltipContent
          v-if="collapsed"
          side="right"
          :side-offset="10"
          class="z-50 rounded-lg border border-[var(--line)] bg-[var(--surface)] px-2.5 py-1.5 text-xs font-medium text-[var(--text)] shadow-xl"
        >
          {{ item.label }}
        </TooltipContent>
      </TooltipRoot>
    </nav>
  </TooltipProvider>
</template>
