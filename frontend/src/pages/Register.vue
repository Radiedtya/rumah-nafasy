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
const showPassword = ref(false)
const saveDevice = ref(false)
const isHumanVerified = ref(false)
const error = ref('')
const isLoading = ref(false)

async function handleRegister() {
  if (password.value !== password_confirmation.value) {
    error.value = 'Konfirmasi password tidak cocok'
    return
  }

  if (!isHumanVerified.value) {
    error.value = 'Mohon verifikasi bahwa Anda adalah manusia'
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
  <div class="min-h-screen bg-white text-neutral-900 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <!-- Heading style ala Cloudflare -->
      <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-neutral-900 leading-tight">
        Build, protect, and connect with Rumah Natasy
      </h2>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div v-if="error" class="mb-4 p-3 bg-red-50 text-red-700 text-xs rounded-xl font-medium border border-red-200">
        {{ error }}
      </div>

      <!-- Social Login Buttons -->
      <div class="space-y-3">
        <button
          type="button"
          class="w-full flex items-center justify-center gap-3 py-3 px-4 border border-neutral-300 rounded-xl text-sm font-medium text-neutral-700 bg-white hover:bg-neutral-50 transition-colors shadow-xs"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
          </svg>
          Continue with Google
        </button>

        <button
          type="button"
          class="w-full flex items-center justify-center gap-3 py-3 px-4 border border-neutral-300 rounded-xl text-sm font-medium text-neutral-700 bg-white hover:bg-neutral-50 transition-colors shadow-xs"
        >
          <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.25c.65-.79 1.09-1.89.97-2.99-.96.04-2.13.64-2.79 1.43-.59.69-1.1 1.78-.96 2.85 1.08.08 2.13-.5 2.78-1.29z"/>
          </svg>
          Continue with Apple
        </button>

        <button
          type="button"
          class="w-full flex items-center justify-center gap-3 py-3 px-4 border border-neutral-300 rounded-xl text-sm font-medium text-neutral-700 bg-white hover:bg-neutral-50 transition-colors shadow-xs"
        >
          <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
            <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
          </svg>
          Continue with GitHub
        </button>
      </div>

      <!-- Garis pemisah -->
      <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
          <div class="w-full border-t border-neutral-200"></div>
        </div>
      </div>

      <!-- Form Pendaftaran Utama -->
      <form class="space-y-4" @submit.prevent="handleRegister">
        <div>
          <label class="block text-xs font-semibold text-neutral-900 mb-1.5">
            Nama Lengkap
          </label>
          <input
            v-model="name"
            type="text"
            required
            class="w-full bg-white border border-neutral-300 rounded-xl px-3.5 py-2.5 text-sm text-neutral-900 outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-all shadow-2xs"
            placeholder="Contoh: Rina Wijaya"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-neutral-900 mb-1.5">
            Email
          </label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full bg-white border border-neutral-300 rounded-xl px-3.5 py-2.5 text-sm text-neutral-900 outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-all shadow-2xs"
            placeholder="nama@email.com"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-neutral-900 mb-1.5">
            Nomor WhatsApp
          </label>
          <input
            v-model="phone"
            type="tel"
            required
            class="w-full bg-white border border-neutral-300 rounded-xl px-3.5 py-2.5 text-sm text-neutral-900 outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-all shadow-2xs"
            placeholder="08123456789"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-neutral-900 mb-1.5">
              Password
            </label>
            <div class="relative">
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                required
                class="w-full bg-white border border-neutral-300 rounded-xl px-3.5 py-2.5 text-sm text-neutral-900 outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-all shadow-2xs pr-10"
                placeholder="••••••••"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-neutral-400 hover:text-neutral-600"
              >
                <!-- Ikon Mata (Show/Hide) -->
                <svg v-if="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-neutral-900 mb-1.5">
              Konfirmasi
            </label>
            <input
              v-model="password_confirmation"
              type="password"
              required
              class="w-full bg-white border border-neutral-300 rounded-xl px-3.5 py-2.5 text-sm text-neutral-900 outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-all shadow-2xs"
              placeholder="••••••••"
            />
          </div>
        </div>

        <!-- Checkbox Save Device -->
        <div class="flex items-center pt-1">
          <label class="relative flex items-center cursor-pointer select-none gap-2.5">
            <input
              v-model="saveDevice"
              type="checkbox"
              class="w-4 h-4 rounded border-neutral-300 text-blue-600 focus:ring-blue-500"
            />
            <span class="text-xs text-neutral-700">Save email and login method on this device</span>
          </label>
        </div>

        <!-- Simulasi Cloudflare Turnstile Verification Box -->
        <div class="space-y-1.5 pt-1">
          <p class="text-xs text-neutral-600">Let us know you are human</p>
          <div class="border border-neutral-200 rounded-xl p-3 bg-white flex items-center justify-between shadow-2xs">
            <label class="flex items-center gap-3 cursor-pointer">
              <input
                v-model="isHumanVerified"
                type="checkbox"
                class="w-5 h-5 rounded border-neutral-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="text-xs font-medium text-neutral-800">Verify you are human</span>
            </label>
            <div class="flex flex-col items-end">
              <!-- Logo Cloudflare Kecil -->
              <div class="flex items-center gap-1 text-[11px] font-bold text-orange-500 tracking-tight">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M16.5 13.5c-.6 0-1.1.2-1.5.5-.6-1.5-2-2.5-3.5-2.5-1.4 0-2.7.9-3.3 2.2C7.3 13.2 6.5 14 6.5 15c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2 0-1-.8-1.8-2-1.5z"/>
                </svg>
                CLOUDFLARE
              </div>
              <div class="text-[9px] text-neutral-400 space-x-1">
                <a href="#" class="hover:underline">Privacy</a>
                <span>•</span>
                <a href="#" class="hover:underline">Help</a>
              </div>
            </div>
          </div>
        </div>

        <!-- Terms agreement text -->
        <p class="text-[11px] text-neutral-500 leading-relaxed pt-1">
          By continuing, I agree to Cloudflare's 
          <a href="#" class="text-neutral-700 underline">terms</a>, 
          <a href="#" class="text-neutral-700 underline">privacy policy</a>, and 
          <a href="#" class="text-neutral-700 underline">cookie policy</a>.
        </p>

        <!-- Submit Button ala Cloudflare -->
        <button
          type="submit"
          class="w-full py-3 px-4 rounded-xl bg-blue-600 text-white font-medium text-sm hover:bg-blue-700 active:scale-[0.99] transition-all shadow-sm cursor-pointer disabled:opacity-50"
          :disabled="isLoading"
        >
          {{ isLoading ? 'Mendaftarkan...' : 'Sign up' }}
        </button>
      </form>

      <!-- Footer Link Login -->
      <div class="text-center pt-6 text-xs text-neutral-600">
        Already have an account?
        <RouterLink to="/login" class="text-blue-600 font-medium hover:underline ml-1">
          Log in
        </RouterLink>
      </div>
    </div>
  </div>
</template>