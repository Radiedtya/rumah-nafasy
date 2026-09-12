<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { LockClosedIcon, ShieldCheckIcon, UserIcon, AcademicCapIcon } from '@heroicons/vue/20/solid'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const isLoading = ref(false)

async function handleLogin() {
  isLoading.value = true
  error.value = ''
  try {
    await auth.login(email.value, password.value)
    router.push('/dashboard')
  } catch (err: any) {
    error.value = err.message || 'Login gagal. Periksa kembali email dan password Anda.'
  } finally {
    isLoading.value = false
  }
}

async function quickDemo(roleEmail: string) {
  email.value = roleEmail
  password.value = 'password'
  await handleLogin()
}
</script>

<template>
  <div class="min-h-screen bg-neutral-50 dark:bg-neutral-950 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-body text-neutral-900 dark:text-neutral-100">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-500 to-rose-400 flex items-center justify-center text-white font-bold text-lg mx-auto shadow-sm mb-3">
        RN
      </div>
      <h2 class="font-display text-2xl font-bold tracking-tight">
        Masuk ke Rumah Natasy
      </h2>
      <p class="mt-1 text-xs text-neutral-500">
        Platform E-Konsultasi Psikologi Online Terpercaya
      </p>
    </div>

    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md px-4">
      <div class="bg-white dark:bg-neutral-900 py-8 px-6 sm:px-8 shadow-xl rounded-3xl border border-neutral-200/80 dark:border-neutral-800 space-y-5">
        <!-- Quick Demo Switcher -->
        <div class="p-3.5 rounded-2xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200/60 dark:border-neutral-800 space-y-2">
          <p class="text-[11px] font-bold text-neutral-600 dark:text-neutral-300 flex items-center gap-1.5">
            <ShieldCheckIcon class="w-4 h-4 text-emerald-500" />
            Akses Cepat Demo Akun:
          </p>
          <div class="grid grid-cols-2 gap-2">
            <button
              type="button"
              class="p-2 rounded-xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 text-left text-xs hover:border-rose-500 transition-colors"
              @click="quickDemo('rina@example.com')"
            >
              <div class="flex items-center gap-1.5 text-sky-600 font-bold mb-0.5">
                <UserIcon class="w-3.5 h-3.5" />
                Pasien Demo
              </div>
              <p class="text-[10px] text-neutral-400">Rina Wijaya</p>
            </button>

            <button
              type="button"
              class="p-2 rounded-xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 text-left text-xs hover:border-rose-500 transition-colors"
              @click="quickDemo('andi@rumahnatasy.id')"
            >
              <div class="flex items-center gap-1.5 text-rose-600 font-bold mb-0.5">
                <AcademicCapIcon class="w-3.5 h-3.5" />
                Psikolog Demo
              </div>
              <p class="text-[10px] text-neutral-400">dr. Andi Pratama</p>
            </button>
          </div>
        </div>

        <div v-if="error" class="p-3 bg-rose-50 text-rose-700 text-xs rounded-xl font-medium">
          {{ error }}
        </div>

        <form class="space-y-4" @submit.prevent="handleLogin">
          <div>
            <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-1 block">
              Email
            </label>
            <input
              v-model="email"
              type="email"
              required
              class="w-full bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 rounded-xl px-3.5 py-2.5 text-xs outline-none focus:border-rose-500"
              placeholder="nama@email.com"
            />
          </div>

          <div>
            <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-1 block">
              Password
            </label>
            <input
              v-model="password"
              type="password"
              required
              class="w-full bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 rounded-xl px-3.5 py-2.5 text-xs outline-none focus:border-rose-500"
              placeholder="••••••••"
            />
          </div>

          <button
            type="submit"
            class="w-full py-3 rounded-xl bg-rose-500 text-white font-bold text-xs hover:bg-rose-600 active:scale-95 transition-all shadow-xs flex items-center justify-center gap-1.5"
            :disabled="isLoading"
          >
            <LockClosedIcon class="w-3.5 h-3.5" />
            <span>{{ isLoading ? 'Memverifikasi...' : 'Masuk Sekarang' }}</span>
          </button>
        </form>

        <div class="text-center pt-2 border-t border-neutral-100 dark:border-neutral-800 text-xs text-neutral-500">
          Belum punya akun?
          <RouterLink to="/register" class="text-rose-600 font-bold hover:underline ml-1">
            Daftar Sekarang
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>
