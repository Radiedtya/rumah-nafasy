<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

// ── Form fields ───────────────────────────────────────────────────────────────
const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)

// ── Errors per-field + form-level ─────────────────────────────────────────────
const errors = ref<{
  name?: string
  email?: string
  password?: string
  passwordConfirmation?: string
  turnstile?: string
  form?: string
}>({})

const isLoading = ref(false)

// ── Cloudflare Turnstile ──────────────────────────────────────────────────────
const SITE_KEY = import.meta.env.VITE_TURNSTILE_SITE_KEY as string
const turnstileToken = ref<string | null>(null)
const turnstileWidgetId = ref<string | null>(null)
const turnstileContainer = ref<HTMLDivElement | null>(null)

declare global {
  interface Window {
    turnstile: {
      render: (container: HTMLElement | string, options: Record<string, unknown>) => string
      reset: (widgetId: string) => void
      remove: (widgetId: string) => void
    }
    _turnstileCbRumahNatasy?: () => void
  }
}

function mountTurnstile() {
  if (!turnstileContainer.value || !window.turnstile) return
  // Guard: jangan mount dua kali
  if (turnstileWidgetId.value !== null) return

  turnstileWidgetId.value = window.turnstile.render(turnstileContainer.value, {
    sitekey: SITE_KEY,
    callback: (token: string) => {
      turnstileToken.value = token
      errors.value.turnstile = undefined
    },
    'error-callback': () => {
      turnstileToken.value = null
      errors.value.turnstile = 'Verifikasi gagal. Muat ulang halaman dan coba lagi.'
    },
    'expired-callback': () => {
      turnstileToken.value = null
      errors.value.turnstile = 'Verifikasi kedaluwarsa. Silakan ulangi.'
    },
    theme: 'light',
    // Tidak set language — biarkan Turnstile auto-detect dari browser
  })
}

function resetTurnstile() {
  if (turnstileWidgetId.value !== null && window.turnstile) {
    window.turnstile.reset(turnstileWidgetId.value)
  }
  turnstileToken.value = null
}

onMounted(() => {
  if (window.turnstile) {
    // Script sudah ada (navigasi balik atau hot-reload)
    mountTurnstile()
    return
  }

  // Nama callback unik agar tidak konflik dengan halaman lain
  const cbName = '_turnstileCbRumahNatasy'
  window[cbName] = () => {
    mountTurnstile()
    delete window[cbName]
  }

  // Cek apakah script turnstile sudah pernah di-inject
  const existing = document.querySelector('script[data-turnstile]')
  if (!existing) {
    const script = document.createElement('script')
    script.src = `https://challenges.cloudflare.com/turnstile/v0/api.js?onload=${cbName}&render=explicit`
    script.async = true
    script.defer = true
    script.dataset.turnstile = '1'
    document.head.appendChild(script)
  }
})

onUnmounted(() => {
  if (turnstileWidgetId.value !== null && window.turnstile) {
    window.turnstile.remove(turnstileWidgetId.value)
    turnstileWidgetId.value = null
  }
})

// ── Validation helpers ────────────────────────────────────────────────────────

function validateName(v: string): string | null {
  const val = v.trim()
  if (!val) return 'Nama lengkap wajib diisi'
  if (val.length < 2) return 'Nama minimal 2 karakter'
  if (val.length > 100) return 'Nama maksimal 100 karakter'
  if (!/^[\p{L}\s'.,-]+$/u.test(val)) return 'Nama hanya boleh berisi huruf dan spasi'
  return null
}

function validateEmail(v: string): string | null {
  const val = v.trim()
  if (!val) return 'Email wajib diisi'
  // RFC 5321 practical regex
  const re = /^[^\s@]{1,64}@[^\s@]{1,253}\.[^\s@]{2,}$/
  if (!re.test(val)) return 'Format email tidak valid'
  if (val.length > 254) return 'Email terlalu panjang'
  return null
}

function validatePassword(v: string): string | null {
  if (!v) return 'Password wajib diisi'
  if (v.length < 8) return 'Password minimal 8 karakter'
  if (v.length > 100) return 'Password terlalu panjang'
  if (!/[A-Z]/.test(v)) return 'Password harus mengandung minimal 1 huruf kapital'
  if (!/[a-z]/.test(v)) return 'Password harus mengandung minimal 1 huruf kecil'
  if (!/[0-9]/.test(v)) return 'Password harus mengandung minimal 1 angka'
  return null
}

function validatePasswordConfirmation(v: string): string | null {
  if (!v) return 'Konfirmasi password wajib diisi'
  if (v !== password.value) return 'Konfirmasi password tidak cocok'
  return null
}

// Password strength score (0–4)
function passwordStrength(v: string): number {
  if (!v) return 0
  let score = 0
  if (v.length >= 8) score++
  if (/[A-Z]/.test(v)) score++
  if (/[0-9]/.test(v)) score++
  if (/[^A-Za-z0-9]/.test(v)) score++
  return score
}

const strengthLabels = ['', 'Lemah', 'Sedang', 'Kuat', 'Sangat Kuat']
const strengthColors = ['', '#ef4444', '#f97316', '#22c55e', '#16a34a']

function validateAll(): boolean {
  const e: typeof errors.value = {}
  const nameErr = validateName(name.value)
  if (nameErr) e.name = nameErr
  const emailErr = validateEmail(email.value)
  if (emailErr) e.email = emailErr
  const pwErr = validatePassword(password.value)
  if (pwErr) e.password = pwErr
  const pcErr = validatePasswordConfirmation(passwordConfirmation.value)
  if (pcErr) e.passwordConfirmation = pcErr
  if (!turnstileToken.value) {
    e.turnstile = 'Mohon selesaikan verifikasi Cloudflare terlebih dahulu'
  }
  errors.value = e
  return Object.keys(e).length === 0
}

// ── Submit ────────────────────────────────────────────────────────────────────

async function handleRegister() {
  if (!validateAll()) return

  isLoading.value = true
  errors.value = {}

  try {
    await auth.register({
      name: name.value.trim(),
      email: email.value.trim(),
      password: password.value,
      password_confirmation: passwordConfirmation.value,
      role: 'pasien',
      turnstile_token: turnstileToken.value!,
    })
    router.push('/dashboard')
  } catch (err: any) {
    resetTurnstile()
    if (err.errors) {
      errors.value = {
        name: err.errors.name?.[0],
        email: err.errors.email?.[0],
        password: err.errors.password?.[0],
        form: !Object.values(err.errors).flat().length ? err.message : undefined,
      }
    } else {
      errors.value = { form: err.message || 'Pendaftaran gagal. Silakan coba lagi.' }
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-white text-neutral-900 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <RouterLink to="/" class="inline-flex items-center gap-2 text-neutral-900 font-extrabold text-xl tracking-tight no-underline mb-2">
        <img src="/icon.svg" alt="" aria-hidden="true" class="w-7 h-7" />
        Rumah Natasy
      </RouterLink>
      <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-neutral-900 leading-tight mt-4">
        Buat akun baru
      </h2>
      <p class="mt-2 text-sm text-neutral-500">Bergabung dan mulai perjalanan kesehatan mental Anda.</p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">

      <!-- Error form-level -->
      <div v-if="errors.form" class="mb-4 p-3 bg-red-50 text-red-700 text-xs rounded-xl font-medium border border-red-200" role="alert">
        {{ errors.form }}
      </div>

      <!-- Google (placeholder — belum terhubung) -->
      <button
        type="button"
        disabled
        aria-label="Daftar dengan Google (segera hadir)"
        class="w-full flex items-center justify-center gap-3 py-3 px-4 border border-neutral-300 rounded-xl text-sm font-medium text-neutral-400 bg-white cursor-not-allowed opacity-55 shadow-xs mb-6"
      >
        <svg class="w-5 h-5" viewBox="0 0 24 24" aria-hidden="true">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
        </svg>
        Daftar dengan Google
      </button>

      <!-- Divider -->
      <div class="relative mb-6">
        <div class="absolute inset-0 flex items-center">
          <div class="w-full border-t border-neutral-200"></div>
        </div>
        <div class="relative flex justify-center text-xs">
          <span class="bg-white px-3 text-neutral-400 font-medium uppercase tracking-widest">Atau daftar dengan email</span>
        </div>
      </div>

      <!-- Form -->
      <form class="space-y-4" @submit.prevent="handleRegister" novalidate>

        <!-- Nama -->
        <div>
          <label for="reg-name" class="block text-xs font-semibold text-neutral-900 mb-1.5">
            Nama Lengkap
          </label>
          <input
            id="reg-name"
            v-model="name"
            type="text"
            autocomplete="name"
            placeholder="Contoh: Rina Wijaya"
            :class="[
              'w-full bg-white border rounded-xl px-3.5 py-2.5 text-sm text-neutral-900 outline-none transition-all shadow-2xs',
              errors.name
                ? 'border-red-400 focus:border-red-500 focus:ring-1 focus:ring-red-400'
                : 'border-neutral-300 focus:border-blue-600 focus:ring-1 focus:ring-blue-600'
            ]"
            @blur="errors.name = validateName(name) ?? undefined"
          />
          <p v-if="errors.name" class="mt-1 text-xs text-red-600" role="alert">{{ errors.name }}</p>
        </div>

        <!-- Email -->
        <div>
          <label for="reg-email" class="block text-xs font-semibold text-neutral-900 mb-1.5">
            Email
          </label>
          <input
            id="reg-email"
            v-model="email"
            type="email"
            autocomplete="email"
            placeholder="nama@email.com"
            :class="[
              'w-full bg-white border rounded-xl px-3.5 py-2.5 text-sm text-neutral-900 outline-none transition-all shadow-2xs',
              errors.email
                ? 'border-red-400 focus:border-red-500 focus:ring-1 focus:ring-red-400'
                : 'border-neutral-300 focus:border-blue-600 focus:ring-1 focus:ring-blue-600'
            ]"
            @blur="errors.email = validateEmail(email) ?? undefined"
          />
          <p v-if="errors.email" class="mt-1 text-xs text-red-600" role="alert">{{ errors.email }}</p>
        </div>

        <!-- Password -->
        <div>
          <label for="reg-password" class="block text-xs font-semibold text-neutral-900 mb-1.5">
            Password
          </label>
          <div class="relative">
            <input
              id="reg-password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="new-password"
              placeholder="Min. 8 karakter, huruf kapital & angka"
              :class="[
                'w-full bg-white border rounded-xl px-3.5 py-2.5 pr-10 text-sm text-neutral-900 outline-none transition-all shadow-2xs',
                errors.password
                  ? 'border-red-400 focus:border-red-500 focus:ring-1 focus:ring-red-400'
                  : 'border-neutral-300 focus:border-blue-600 focus:ring-1 focus:ring-blue-600'
              ]"
              @blur="errors.password = validatePassword(password) ?? undefined"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute inset-y-0 right-0 pr-3 flex items-center text-neutral-400 hover:text-neutral-600"
              :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
            >
              <svg v-if="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
              </svg>
            </button>
          </div>
          <!-- Password strength bar -->
          <div v-if="password" class="mt-2 space-y-1">
            <div class="flex gap-1">
              <div
                v-for="i in 4"
                :key="i"
                class="h-1 flex-1 rounded-full transition-colors duration-300"
                :style="{ background: i <= passwordStrength(password) ? strengthColors[passwordStrength(password)] : '#e5e7eb' }"
              ></div>
            </div>
            <p class="text-xs" :style="{ color: strengthColors[passwordStrength(password)] }">
              {{ strengthLabels[passwordStrength(password)] }}
            </p>
          </div>
          <p v-if="errors.password" class="mt-1 text-xs text-red-600" role="alert">{{ errors.password }}</p>
        </div>

        <!-- Konfirmasi Password -->
        <div>
          <label for="reg-confirm" class="block text-xs font-semibold text-neutral-900 mb-1.5">
            Konfirmasi Password
          </label>
          <input
            id="reg-confirm"
            v-model="passwordConfirmation"
            type="password"
            autocomplete="new-password"
            placeholder="Ulangi password Anda"
            :class="[
              'w-full bg-white border rounded-xl px-3.5 py-2.5 text-sm text-neutral-900 outline-none transition-all shadow-2xs',
              errors.passwordConfirmation
                ? 'border-red-400 focus:border-red-500 focus:ring-1 focus:ring-red-400'
                : passwordConfirmation && !errors.passwordConfirmation
                  ? 'border-green-400 focus:border-green-500 focus:ring-1 focus:ring-green-400'
                  : 'border-neutral-300 focus:border-blue-600 focus:ring-1 focus:ring-blue-600'
            ]"
            @blur="errors.passwordConfirmation = validatePasswordConfirmation(passwordConfirmation) ?? undefined"
          />
          <p v-if="errors.passwordConfirmation" class="mt-1 text-xs text-red-600" role="alert">{{ errors.passwordConfirmation }}</p>
          <p v-else-if="passwordConfirmation && passwordConfirmation === password" class="mt-1 text-xs text-green-600">
            Password cocok ✓
          </p>
        </div>

        <!-- Cloudflare Turnstile -->
        <div class="pt-1">
          <p class="text-xs text-neutral-500 mb-2">Verifikasi keamanan</p>
          <div ref="turnstileContainer" class="min-h-[65px]" aria-label="Widget verifikasi Cloudflare Turnstile"></div>
          <p v-if="errors.turnstile" class="mt-1.5 text-xs text-red-600" role="alert">{{ errors.turnstile }}</p>
        </div>

        <!-- Terms -->
        <p class="text-[11px] text-neutral-500 leading-relaxed pt-1">
          Dengan mendaftar, saya menyetujui
          <a href="#" class="text-neutral-700 underline">Ketentuan Layanan</a>,
          <a href="#" class="text-neutral-700 underline">Kebijakan Privasi</a>, dan
          <a href="#" class="text-neutral-700 underline">Kebijakan Cookie</a> Rumah Natasy.
        </p>

        <!-- Submit -->
        <button
          type="submit"
          class="w-full py-3 px-4 rounded-xl bg-blue-600 text-white font-medium text-sm hover:bg-blue-700 active:scale-[0.99] transition-all shadow-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="isLoading"
        >
          {{ isLoading ? 'Mendaftarkan...' : 'Buat Akun' }}
        </button>
      </form>

      <!-- Link Login -->
      <div class="text-center pt-6 text-xs text-neutral-600">
        Sudah punya akun?
        <RouterLink to="/login" class="text-blue-600 font-medium hover:underline ml-1">
          Masuk sekarang
        </RouterLink>
      </div>
    </div>
  </div>
</template>
