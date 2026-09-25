<script setup lang="ts">
import {
  DropdownMenuRoot,
  DropdownMenuTrigger,
  DropdownMenuPortal,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
} from 'reka-ui'
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'
import { computed } from 'vue'
import {
  ArrowRightStartOnRectangleIcon,
  Cog6ToothIcon,
  FaceSmileIcon,
  ComputerDesktopIcon,
  SunIcon,
  MoonIcon,
  HomeIcon,
  PencilSquareIcon,
  LifebuoyIcon,
  BookOpenIcon,
} from '@heroicons/vue/24/outline'
import { useTheme, type ThemeMode } from '../../composables/theme'

const auth = useAuthStore()
const router = useRouter()
const { mode, setMode } = useTheme()

interface Props {
  compact?: boolean
}

withDefaults(defineProps<Props>(), {
  compact: false,
})

const initials = computed(() => {
  const n = auth.user?.name?.trim()
  if (!n) return '·'
  return n
    .split(/\s+/)
    .slice(0, 2)
    .map((w) => w.charAt(0).toUpperCase())
    .join('')
})

const themeOptions: { value: ThemeMode; icon: any; label: string }[] = [
  { value: 'system', icon: ComputerDesktopIcon, label: 'Ikuti sistem' },
  { value: 'light', icon: SunIcon, label: 'Terang' },
  { value: 'dark', icon: MoonIcon, label: 'Gelap' },
]

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <DropdownMenuRoot>
    <DropdownMenuTrigger
      class="flex w-full items-center gap-2.5 rounded-xl p-2 text-left outline-none transition-colors duration-150 hover:bg-[var(--sidebar-hover)] focus-visible:ring-2 focus-visible:ring-[var(--accent)]/30 data-[state=open]:bg-[var(--sidebar-hover)]"
      :class="compact ? 'justify-center' : ''"
    >
      <span
        class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[var(--accent)]/12 text-xs font-semibold text-[var(--accent)] ring-1 ring-[var(--accent)]/15"
      >
        <img v-if="auth.user?.avatar" :src="auth.user.avatar" :alt="auth.user.name || 'Foto profil'" class="h-full w-full object-cover" />
        <span v-else>{{ initials }}</span>
      </span>
      <template v-if="!compact">
        <span class="min-w-0 flex-1">
          <span class="block truncate text-xs font-semibold text-[var(--sidebar-text-active)]">
            {{ auth.user?.name || 'Pengguna' }}
          </span>
          <span class="block truncate text-[10px] text-[var(--sidebar-text)]">
            {{ auth.user?.email || '' }}
          </span>
        </span>
        <svg
          class="h-4 w-4 shrink-0 text-[var(--sidebar-text)]"
          viewBox="0 0 16 16"
          fill="currentColor"
          aria-hidden="true"
        >
          <path d="M8 2l3 4H5l3-4zM8 14l-3-4h6l-3 4z" />
        </svg>
      </template>
    </DropdownMenuTrigger>

    <DropdownMenuPortal>
      <DropdownMenuContent
        side="top"
        align="start"
        :side-offset="8"
        class="menu-content z-50 w-72 overflow-hidden rounded-2xl border border-[var(--line)] bg-[var(--surface)] shadow-2xl"
      >
        <!-- ===== Header: nama, email, gear ===== -->
        <div class="flex items-center gap-3 px-4 py-3.5">
          <div class="min-w-0 flex-1">
            <p class="truncate text-[13px] font-semibold text-[var(--text)]">
              {{ auth.user?.name || 'Pengguna' }}
            </p>
            <p class="mt-0.5 truncate text-xs text-[var(--muted)]">
              {{ auth.user?.email || '' }}
            </p>
          </div>
          <DropdownMenuItem
            value="settings"
            class="cursor-pointer rounded-lg p-1.5 text-[var(--muted)] outline-none transition-colors hover:bg-[var(--muted)]/10 hover:text-[var(--text)] data-[highlighted]:bg-[var(--muted)]/10"
            @click="router.push('/dashboard/profil')"
          >
            <Cog6ToothIcon class="h-4.5 w-4.5" />
          </DropdownMenuItem>
        </div>

        <DropdownMenuSeparator class="h-px bg-[var(--line)]" />

        <!-- ===== Feedback ===== -->
        <div class="p-1.5">
          <DropdownMenuItem
            value="feedback"
            class="flex cursor-pointer items-center justify-between rounded-lg px-2.5 py-2 text-[13px] font-medium text-[var(--text)] outline-none transition-colors data-[highlighted]:bg-[var(--muted)]/10"
            @click="() => {}"
          >
            Feedback
            <FaceSmileIcon class="h-4.5 w-4.5 text-[var(--muted)]" />
          </DropdownMenuItem>

          <!-- ===== Theme: segmented control ===== -->
          <div class="flex items-center justify-between rounded-lg px-2.5 py-2 text-[13px] font-medium text-[var(--text)]">
            Theme
            <div class="flex items-center gap-0.5 rounded-lg bg-[var(--muted)]/10 p-0.5">
              <button
                v-for="opt in themeOptions"
                :key="opt.value"
                type="button"
                class="flex h-7 w-8 items-center justify-center rounded-md transition-all duration-150 outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent)]/30"
                :class="
                  mode === opt.value
                    ? 'bg-[var(--surface)] shadow-sm ring-1 ring-[var(--line)]'
                    : 'text-[var(--muted)] hover:text-[var(--text)]'
                "
                :title="opt.label"
                :aria-label="opt.label"
                :aria-pressed="mode === opt.value"
                @click="setMode(opt.value)"
              >
                <component :is="opt.icon" class="h-4 w-4" />
              </button>
            </div>
          </div>

          <DropdownMenuItem
            value="home"
            class="flex cursor-pointer items-center justify-between rounded-lg px-2.5 py-2 text-[13px] font-medium text-[var(--text)] outline-none transition-colors data-[highlighted]:bg-[var(--muted)]/10"
            @click="router.push('/')"
          >
            Home Page
            <HomeIcon class="h-4.5 w-4.5 text-[var(--muted)]" />
          </DropdownMenuItem>

          <DropdownMenuItem
            value="changelog"
            class="flex cursor-pointer items-center justify-between rounded-lg px-2.5 py-2 text-[13px] font-medium text-[var(--text)] outline-none transition-colors data-[highlighted]:bg-[var(--muted)]/10"
            @click="() => {}"
          >
            Changelog
            <PencilSquareIcon class="h-4.5 w-4.5 text-[var(--muted)]" />
          </DropdownMenuItem>

          <DropdownMenuItem
            value="help"
            class="flex cursor-pointer items-center justify-between rounded-lg px-2.5 py-2 text-[13px] font-medium text-[var(--text)] outline-none transition-colors data-[highlighted]:bg-[var(--muted)]/10"
            @click="() => {}"
          >
            Help
            <LifebuoyIcon class="h-4.5 w-4.5 text-[var(--muted)]" />
          </DropdownMenuItem>

          <DropdownMenuItem
            value="docs"
            class="flex cursor-pointer items-center justify-between rounded-lg px-2.5 py-2 text-[13px] font-medium text-[var(--text)] outline-none transition-colors data-[highlighted]:bg-[var(--muted)]/10"
            @click="() => {}"
          >
            Docs
            <BookOpenIcon class="h-4.5 w-4.5 text-[var(--muted)]" />
          </DropdownMenuItem>

          <DropdownMenuItem
            value="logout"
            class="mt-1 flex cursor-pointer items-center justify-between rounded-lg px-2.5 py-2 text-[13px] font-medium text-[var(--text)] outline-none transition-colors data-[highlighted]:bg-[var(--muted)]/10"
            @click="handleLogout"
          >
            Log Out
            <ArrowRightStartOnRectangleIcon class="h-4.5 w-4.5 text-[var(--muted)]" />
          </DropdownMenuItem>
        </div>

        <DropdownMenuSeparator class="h-px bg-[var(--line)]" />

        <!-- ===== Upgrade to Pro ===== -->
        <div class="p-3">
          <button
            type="button"
            class="w-full rounded-xl bg-[var(--inverse)] py-2.5 text-[13px] font-semibold text-[var(--inverse-text)] transition-opacity hover:opacity-90 focus-visible:ring-2 focus-visible:ring-[var(--accent)]/30 focus-visible:outline-none"
          >
            Upgrade to Pro
          </button>
        </div>

        <!-- ===== Status bar ===== -->
        <div
          class="flex items-center justify-between px-4 py-2.5 text-xs font-medium"
          style="background: color-mix(in srgb, var(--accent) 8%, var(--surface))"
        >
          <span class="text-[var(--accent)]">All systems normal.</span>
          <span class="h-2.5 w-2.5 rounded-full bg-[var(--accent)]" />
        </div>
      </DropdownMenuContent>
    </DropdownMenuPortal>
  </DropdownMenuRoot>
</template>
