<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'
import {
  DropdownMenuRoot,
  DropdownMenuTrigger,
  DropdownMenuPortal,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
} from 'reka-ui'
import { ArrowPathIcon, UserIcon, AcademicCapIcon } from '@heroicons/vue/24/outline'

const auth = useAuthStore()
const router = useRouter()
const isSwitching = ref(false)

const demoAccounts = [
  {
    role: 'pasien',
    name: 'Rina Wijaya (Pasien)',
    email: 'rina@example.com',
    password: 'password',
    desc: 'Pasien umum untuk reservasi & konsultasi',
    icon: UserIcon,
  },
  {
    role: 'psikolog',
    name: 'dr. Andi Pratama (Psikolog)',
    email: 'andi@rumahnatasy.id',
    password: 'password',
    desc: 'Psikolog spesialis klinis dewasa & jadwal',
    icon: AcademicCapIcon,
  },
  {
    role: 'pasien',
    name: 'Fajar Nugroho (Pasien)',
    email: 'fajar@example.com',
    password: 'password',
    desc: 'Pasien dengan kebutuhan konseling',
    icon: UserIcon,
  },
  {
    role: 'psikolog',
    name: 'dr. Sari Dewi (Psikolog)',
    email: 'sari@rumahnatasy.id',
    password: 'password',
    desc: 'Psikolog anak, remaja & kecemasan',
    icon: AcademicCapIcon,
  },
]

async function switchAccount(acc: (typeof demoAccounts)[0]) {
  isSwitching.value = true
  try {
    await auth.login(acc.email, acc.password)
    router.push('/dashboard')
  } catch (e) {
    console.error('Failed switching account', e)
  } finally {
    isSwitching.value = false
  }
}
</script>

<template>
  <DropdownMenuRoot>
    <DropdownMenuTrigger
      class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--line)] bg-[var(--surface)] px-2.5 py-1.5 text-xs font-medium text-[var(--text)] shadow-xs outline-none transition-colors duration-150 hover:bg-[var(--muted)]/8 focus-visible:ring-2 focus-visible:ring-[var(--accent)]/30 data-[state=open]:bg-[var(--muted)]/8"
      :disabled="isSwitching"
    >
      <ArrowPathIcon
        class="h-3.5 w-3.5 text-[var(--accent)]"
        :class="{ 'animate-spin': isSwitching }"
      />
      <span class="hidden sm:inline">Switch Akun Demo</span>
      <span class="sm:hidden">Demo</span>
      <span
        class="rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide"
        :class="
          auth.isPsikolog
            ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400'
            : 'bg-sky-500/10 text-sky-600 dark:text-sky-400'
        "
      >
        {{ auth.isPsikolog ? 'Psikolog' : 'Pasien' }}
      </span>
    </DropdownMenuTrigger>

    <DropdownMenuPortal>
      <DropdownMenuContent
        align="end"
        :side-offset="8"
        class="menu-content z-50 w-72 rounded-xl border border-[var(--line)] bg-[var(--surface)] p-1.5 shadow-xl"
      >
        <DropdownMenuLabel class="px-2.5 py-2">
          <span class="block text-xs font-semibold text-[var(--text)]">Uji Coba Sinkronisasi Role</span>
          <span class="mt-0.5 block text-[10px] text-[var(--muted)]">
            Pilih akun demo dari seeder Laravel
          </span>
        </DropdownMenuLabel>
        <DropdownMenuSeparator class="my-1 h-px bg-[var(--line)]" />

        <DropdownMenuItem
          v-for="acc in demoAccounts"
          :key="acc.email"
          :value="acc.email"
          class="flex cursor-pointer items-start gap-2.5 rounded-lg px-2.5 py-2 outline-none transition-colors data-[highlighted]:bg-[var(--muted)]/10"
          @click="switchAccount(acc)"
        >
          <span
            class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md"
            :class="
              acc.role === 'psikolog'
                ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400'
                : 'bg-sky-500/10 text-sky-600 dark:text-sky-400'
            "
          >
            <component :is="acc.icon" class="h-3.5 w-3.5" />
          </span>
          <span class="min-w-0">
            <span class="block truncate text-xs font-medium text-[var(--text)]">
              {{ acc.name }}
            </span>
            <span class="mt-0.5 block text-[10px] leading-tight text-[var(--muted)]">
              {{ acc.desc }}
            </span>
          </span>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenuPortal>
  </DropdownMenuRoot>
</template>
