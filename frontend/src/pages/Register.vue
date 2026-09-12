<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const name = ref('')
const email = ref('')
const phone = ref('')
const password = ref('')
const password_confirmation = ref('')
const error = ref('')
const isLoading = ref(false)

async function handleRegister() {
  if (password.value !== password_confirmation.value) {
    error.value = 'Konfirmasi password tidak cocok'
    return
  }

  isLoading.value = true
  error.value = ''

  try {
    await auth.register({
      name: name.value,
      email: email.value,
      phone: phone.value,
      password: password.value,
      password_confirmation: password_confirmation.value,
      role: 'pasien',
    })
    router.push('/dashboard')
  } catch (err: any) {
    error.value = err.message || 'Pendaftaran gagal'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-neutral-50 dark:bg-neutral-950 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-body text-neutral-900 dark:text-neutral-100">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-500 to-rose-400 flex items-center justify-center text-white font-bold text-lg mx-auto shadow-sm mb-3">
        RN
      </div>
      <h2 class="font-display text-2xl font-bold tracking-tight">
        Daftar Akun Baru
      </h2>
      <p class="mt-1 text-xs text-neutral-500">
        Mulai konsultasi dengan psikolog profesional di Rumah Natasy
      </p>
    </div>

    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md px-4">
      <div class="bg-white dark:bg-neutral-900 py-8 px-6 sm:px-8 shadow-xl rounded-3xl border border-neutral-200/80 dark:border-neutral-800 space-y-4">
        <div v-if="error" class="p-3 bg-rose-50 text-rose-700 text-xs rounded-xl font-medium">
          {{ error }}
        </div>

        <form class="space-y-3.5" @submit.prevent="handleRegister">
          <div>
            <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-1 block">
              Nama Lengkap
            </label>
            <input
              v-model="name"
              type="text"
              required
              class="w-full bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 rounded-xl px-3.5 py-2.5 text-xs outline-none focus:border-rose-500"
              placeholder="Contoh: Rina Wijaya"
            />
          </div>

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
              Nomor WhatsApp
            </label>
            <input
              v-model="phone"
              type="tel"
              required
              class="w-full bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 rounded-xl px-3.5 py-2.5 text-xs outline-none focus:border-rose-500"
              placeholder="08123456789"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
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

            <div>
              <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-1 block">
                Konfirmasi
              </label>
              <input
                v-model="password_confirmation"
                type="password"
                required
                class="w-full bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 rounded-xl px-3.5 py-2.5 text-xs outline-none focus:border-rose-500"
                placeholder="••••••••"
              />
            </div>
          </div>

          <button
            type="submit"
            class="w-full py-3 rounded-xl bg-rose-500 text-white font-bold text-xs hover:bg-rose-600 active:scale-95 transition-all shadow-xs"
            :disabled="isLoading"
          >
            {{ isLoading ? 'Mendaftarkan...' : 'Buat Akun Sekarang' }}
          </button>
        </form>

        <div class="text-center pt-2 border-t border-neutral-100 dark:border-neutral-800 text-xs text-neutral-500">
          Sudah punya akun?
          <RouterLink to="/login" class="text-rose-600 font-bold hover:underline ml-1">
            Masuk ke Akun
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>
