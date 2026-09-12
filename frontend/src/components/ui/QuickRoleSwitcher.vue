<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'
import {
  UserIcon,
  AcademicCapIcon,
  ArrowPathIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/20/solid'

const auth = useAuthStore()
const router = useRouter()
const isSwitching = ref(false)
const showMenu = ref(false)

const demoAccounts = [
  {
    role: 'pasien',
    name: 'Rina Wijaya (Pasien)',
    email: 'rina@example.com',
    password: 'password',
    desc: 'Pasien umum untuk reservasi & konsultasi',
    icon: UserIcon,
    color: 'text-sky-600 bg-sky-50 dark:bg-sky-950/40',
  },
  {
    role: 'psikolog',
    name: 'dr. Andi Pratama (Psikolog)',
    email: 'andi@rumahnatasy.id',
    password: 'password',
    desc: 'Psikolog spesialis klinis dewasa & jadwal',
    icon: AcademicCapIcon,
    color: 'text-rose-600 bg-rose-50 dark:bg-rose-950/40',
  },
  {
    role: 'pasien',
    name: 'Fajar Nugroho (Pasien)',
    email: 'fajar@example.com',
    password: 'password',
    desc: 'Pasien dengan kebutuhan konseling',
    icon: UserIcon,
    color: 'text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40',
  },
  {
    role: 'psikolog',
    name: 'dr. Sari Dewi (Psikolog)',
    email: 'sari@rumahnatasy.id',
    password: 'password',
    desc: 'Psikolog anak, remaja & kecemasan',
    icon: AcademicCapIcon,
    color: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40',
  },
]

async function switchAccount(acc: (typeof demoAccounts)[0]) {
  isSwitching.value = true
  showMenu.value = false
  try {
    await auth.login(acc.email, acc.password)
    if (acc.role === 'psikolog') {
      router.push('/dashboard')
    } else {
      router.push('/dashboard')
    }
  } catch (e) {
    console.error('Failed switching account', e)
  } finally {
    isSwitching.value = false
  }
}
</script>

<template>
  <div class="relative">
    <button
      type="button"
      class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 text-xs font-medium text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors shadow-xs"
      :disabled="isSwitching"
      @click="showMenu = !showMenu"
    >
      <ArrowPathIcon class="w-3.5 h-3.5 text-rose-500" :class="{ 'animate-spin': isSwitching }" />
      <span>Switch Akun Demo</span>
      <span
        class="text-[10px] uppercase font-bold px-1.5 py-0.2 rounded"
        :class="auth.isPsikolog ? 'bg-rose-100 text-rose-700' : 'bg-sky-100 text-sky-700'"
      >
        {{ auth.isPsikolog ? 'Psikolog' : 'Pasien' }}
      </span>
    </button>

    <div
      v-if="showMenu"
      class="fixed inset-0 z-40"
      @click="showMenu = false"
    />

    <div
      v-if="showMenu"
      class="absolute right-0 mt-2 w-72 rounded-2xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 shadow-xl p-2 z-50 animate-in fade-in zoom-in-95 duration-100"
    >
      <div class="px-2.5 py-2 border-b border-neutral-100 dark:border-neutral-800 mb-1">
        <p class="text-xs font-semibold text-neutral-900 dark:text-neutral-100 flex items-center gap-1.5">
          <ShieldCheckIcon class="w-4 h-4 text-emerald-500" />
          Uji Coba Sinkronisasi Role
        </p>
        <p class="text-[11px] text-neutral-500 mt-0.5">Pilih akun demo dari seeder Laravel:</p>
      </div>

      <div class="space-y-1">
        <button
          v-for="acc in demoAccounts"
          :key="acc.email"
          type="button"
          class="w-full text-left flex items-start gap-2.5 p-2 rounded-xl hover:bg-neutral-50 dark:hover:bg-neutral-800/80 transition-colors"
          :class="{ 'ring-1 ring-rose-500 bg-rose-50/50 dark:bg-rose-950/20': auth.user?.email === acc.email }"
          @click="switchAccount(acc)"
        >
          <div class="p-1.5 rounded-lg shrink-0" :class="acc.color">
            <component :is="acc.icon" class="w-4 h-4" />
          </div>
          <div class="min-w-0">
            <p class="text-xs font-semibold text-neutral-900 dark:text-neutral-100 truncate">
              {{ acc.name }}
            </p>
            <p class="text-[10px] text-neutral-500 dark:text-neutral-400 leading-tight mt-0.5">
              {{ acc.desc }}
            </p>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>
