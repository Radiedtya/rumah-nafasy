<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import type { UserProfile } from '../stores/auth'
import { apiFetch } from '../lib/api'
import { useTheme } from '../composables/theme'
import { useGooglePopup } from '../composables/googleAuth'

const router = useRouter()
const auth = useAuthStore()
const { resolvedMode, setMode } = useTheme()

// ── Daftar/Masuk dengan Google — satu tombol, tidak ada dead end ─────────────
// Mekanisme backend: akun ada → langsung login; akun belum ada → dibuatkan.

const { open: openGooglePopup, fallbackRedirect, loginUrl } = useGooglePopup()
const googleLoading = ref(false)

async function completeGoogleAuth(code: string) {
  try {
    const res = await apiFetch<{ user: UserProfile; token: string }>('auth/google/exchange', {
      method: 'POST',
      body: JSON.stringify({ code }),
    })
    auth.setSession(res.data.token, res.data.user)
    router.push('/dashboard')
  } catch (err: any) {
    errors.value = { form: err.message || 'Gagal menyelesaikan login Google.' }
  } finally {
    googleLoading.value = false
  }
}

function handleGoogleRegister() {
  errors.value = {}
  googleLoading.value = true

  const ok = openGooglePopup(loginUrl(), {
    onCode: (code) => completeGoogleAuth(code),
    onError: (kind) => {
      googleLoading.value = false
      if (kind === 'blocked') {
        errors.value = { form: 'Akun Anda dinonaktifkan — hubungi admin Rumah Nafasy.' }
      } else {
        errors.value = { form: 'Login Google gagal — coba lagi.' }
      }
    },
    onDismissed: () => {
      googleLoading.value = false
    },
  })

  if (!ok) {
    fallbackRedirect(loginUrl())
  }
}

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
    _turnstileCbrumahNafasy?: () => void
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
  const cbName = '_turnstileCbrumahNafasy'
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
    const result = await auth.register({
      name: name.value.trim(),
      email: email.value.trim(),
      password: password.value,
      password_confirmation: passwordConfirmation.value,
      role: 'pasien',
      turnstile_token: turnstileToken.value!,
    })
    // Pendaftaran disimpan PENDING — akun baru dibuat setelah OTP benar.
    // verify_handle = pengenal sesi OTP yang wajib dibawa ke halaman verifikasi.
    router.push({
      path: '/verify-email',
      query: {
        email: result.email ?? email.value.trim(),
        vh: result.verify_handle ?? '',
      },
    })
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
  <div :data-theme="resolvedMode" class="reg-page">
    <!-- Theme toggle -->
    <button
      type="button"
      class="reg-theme-toggle"
      :aria-label="resolvedMode === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
      @click="setMode(resolvedMode === 'dark' ? 'light' : 'dark')"
    >
      <svg v-if="resolvedMode === 'dark'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
      </svg>
      <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
      </svg>
    </button>

    <div class="reg-inner">
      <div class="reg-header">
        <RouterLink to="/" class="reg-brand" aria-label="Kembali ke beranda Rumah Nafasy">
          <img src="/icon.svg" alt="" aria-hidden="true" />
          Rumah Nafasy
        </RouterLink>
        <h2 class="reg-heading">Buat akun baru</h2>
        <p class="reg-subheading">Bergabung dan mulai perjalanan kesehatan mental Anda.</p>
      </div>

      <!-- Error form-level -->
      <div v-if="errors.form" class="reg-error-box" role="alert">
        {{ errors.form }}
      </div>

      <!-- Google — akun ada → login; belum ada → dibuat otomatis -->
      <button
        type="button"
        class="reg-google-btn reg-google-btn--active"
        :disabled="googleLoading"
        aria-label="Daftar atau masuk dengan Google"
        @click="handleGoogleRegister"
      >
        <svg class="reg-google-icon" viewBox="0 0 24 24" aria-hidden="true">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
        </svg>
        {{ googleLoading ? 'Memproses…' : 'Daftar / Masuk dengan Google' }}
      </button>

      <!-- Divider -->
      <div class="reg-divider"><span>Atau daftar dengan email</span></div>

      <!-- Form -->
      <form class="reg-form" @submit.prevent="handleRegister" novalidate>

        <!-- Nama -->
        <div class="reg-field">
          <label for="reg-name">Nama Lengkap</label>
          <input
            id="reg-name"
            v-model="name"
            type="text"
            autocomplete="name"
            placeholder="Contoh: Rina Wijaya"
            :class="{ 'is-error': errors.name }"
            @blur="errors.name = validateName(name) ?? undefined"
          />
          <span v-if="errors.name" class="reg-field-error" role="alert">{{ errors.name }}</span>
        </div>

        <!-- Email -->
        <div class="reg-field">
          <label for="reg-email">Email</label>
          <input
            id="reg-email"
            v-model="email"
            type="email"
            autocomplete="email"
            placeholder="nama@email.com"
            :class="{ 'is-error': errors.email }"
            @blur="errors.email = validateEmail(email) ?? undefined"
          />
          <span v-if="errors.email" class="reg-field-error" role="alert">{{ errors.email }}</span>
        </div>

        <!-- Password -->
        <div class="reg-field">
          <label for="reg-password">Password</label>
          <div class="reg-password-wrap">
            <input
              id="reg-password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="new-password"
              placeholder="Min. 8 karakter, huruf kapital & angka"
              :class="{ 'is-error': errors.password }"
              @blur="errors.password = validatePassword(password) ?? undefined"
            />
            <button
              type="button"
              class="reg-eye-btn"
              :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
              @click="showPassword = !showPassword"
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
          <!-- Strength bar -->
          <div v-if="password" class="reg-strength">
            <div class="reg-strength-bars">
              <div
                v-for="i in 4" :key="i"
                class="reg-strength-bar"
                :style="{ background: i <= passwordStrength(password) ? strengthColors[passwordStrength(password)] : 'var(--strength-empty)' }"
              />
            </div>
            <span :style="{ color: strengthColors[passwordStrength(password)] }">
              {{ strengthLabels[passwordStrength(password)] }}
            </span>
          </div>
          <span v-if="errors.password" class="reg-field-error" role="alert">{{ errors.password }}</span>
        </div>

        <!-- Konfirmasi -->
        <div class="reg-field">
          <label for="reg-confirm">Konfirmasi Password</label>
          <input
            id="reg-confirm"
            v-model="passwordConfirmation"
            type="password"
            autocomplete="new-password"
            placeholder="Ulangi password Anda"
            :class="{
              'is-error': errors.passwordConfirmation,
              'is-ok': passwordConfirmation && !errors.passwordConfirmation && passwordConfirmation === password,
            }"
            @blur="errors.passwordConfirmation = validatePasswordConfirmation(passwordConfirmation) ?? undefined"
          />
          <span v-if="errors.passwordConfirmation" class="reg-field-error" role="alert">{{ errors.passwordConfirmation }}</span>
          <span v-else-if="passwordConfirmation && passwordConfirmation === password" class="reg-field-ok">Password cocok ✓</span>
        </div>

        <!-- Turnstile -->
        <div class="reg-turnstile-wrap">
          <p class="reg-turnstile-label">Verifikasi keamanan</p>
          <div ref="turnstileContainer" class="reg-turnstile-widget" aria-label="Widget verifikasi Cloudflare Turnstile"></div>
          <span v-if="errors.turnstile" class="reg-field-error" role="alert">{{ errors.turnstile }}</span>
        </div>

        <!-- Terms -->
        <p class="reg-terms">
          Dengan mendaftar, saya menyetujui
          <a href="#">Ketentuan Layanan</a>,
          <a href="#">Kebijakan Privasi</a>, dan
          <a href="#">Kebijakan Cookie</a> Rumah Nafasy.
        </p>

        <!-- Submit -->
        <button type="submit" class="reg-submit" :disabled="isLoading">
          {{ isLoading ? 'Mendaftarkan...' : 'Buat Akun' }}
        </button>
      </form>

      <!-- Footer -->
      <p class="reg-switch">
        Sudah punya akun?
        <RouterLink to="/login">Masuk sekarang</RouterLink>
      </p>
    </div>
  </div>
</template>

<style scoped>
/* ── Tokens ─────────────────────────────────────────────────────────── */
.reg-page {
  --bg: #ffffff;
  --text: #111318;
  --text-muted: #6b7280;
  --border: #d1d5db;
  --input-bg: #ffffff;
  --input-text: #111318;
  --input-ph: #9ca3af;
  --focus-ring: rgb(37 99 235 / 15%);
  --error: #dc2626;
  --error-bg: #fef2f2;
  --ok: #16a34a;
  --btn-bg: #2563eb;
  --btn-hover: #1d4ed8;
  --link: #2563eb;
  --terms-text: #6b7280;
  --terms-link: #374151;
  --divider: #e5e7eb;
  --divider-text: #9ca3af;
  --google-border: #d1d5db;
  --google-text: #6b7280;
  --toggle-border: #d1d5db;
  --toggle-text: #6b7280;
  --strength-empty: #e5e7eb;
}
.reg-page[data-theme='dark'] {
  --bg: #0f1117;
  --text: #f0f2f5;
  --text-muted: #8992a2;
  --border: #272b36;
  --input-bg: #191c27;
  --input-text: #f0f2f5;
  --input-ph: #3d4455;
  --focus-ring: rgb(59 130 246 / 20%);
  --error: #f87171;
  --error-bg: #2a1a1a;
  --ok: #4ade80;
  --btn-bg: #2563eb;
  --btn-hover: #3b82f6;
  --link: #60a5fa;
  --terms-text: #555d6e;
  --terms-link: #8992a2;
  --divider: #272b36;
  --divider-text: #444d5e;
  --google-border: #272b36;
  --google-text: #555d6e;
  --toggle-border: #272b36;
  --toggle-text: #555d6e;
  --strength-empty: #272b36;
}

/* ── Shell ──────────────────────────────────────────────────────────── */
.reg-page {
  min-height: 100svh;
  background: var(--bg);
  color: var(--text);
  font-family: var(--font-body, system-ui, sans-serif);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 16px;
  position: relative;
}

/* ── Theme toggle ───────────────────────────────────────────────────── */
.reg-theme-toggle {
  position: fixed;
  top: 16px;
  right: 16px;
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--toggle-border);
  border-radius: 8px;
  background: transparent;
  color: var(--toggle-text);
  cursor: pointer;
  transition: background 160ms, color 160ms, border-color 160ms;
  z-index: 10;
}
.reg-theme-toggle svg { width: 16px; height: 16px; }
.reg-theme-toggle:hover { background: var(--border); color: var(--text); }

/* ── Inner container ────────────────────────────────────────────────── */
.reg-inner { width: 100%; max-width: 440px; }

/* ── Header ─────────────────────────────────────────────────────────── */
.reg-header { margin-bottom: 28px; }
.reg-brand {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--text);
  font-size: 20px;
  font-weight: 800;
  letter-spacing: -0.05em;
  text-decoration: none;
  margin-bottom: 20px;
}
.reg-brand img { width: 26px; height: 26px; object-fit: contain; }
.reg-heading {
  margin: 0 0 6px;
  font-size: 28px;
  font-weight: 700;
  letter-spacing: -0.04em;
  line-height: 1.1;
  color: var(--text);
}
.reg-subheading { margin: 0; font-size: 14px; color: var(--text-muted); }

/* ── Error box ──────────────────────────────────────────────────────── */
.reg-error-box {
  margin-bottom: 16px;
  padding: 10px 14px;
  border-radius: 10px;
  background: var(--error-bg);
  color: var(--error);
  font-size: 12.5px;
  border: 1px solid color-mix(in srgb, var(--error) 20%, transparent);
}

/* ── Google button ──────────────────────────────────────────────────── */
.reg-google-btn {
  width: 100%;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border: 1px solid var(--google-border);
  border-radius: 10px;
  background: var(--input-bg);
  color: var(--google-text);
  font: inherit;
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 20px;
}
.reg-google-btn--active { cursor: pointer; opacity: 1; transition: background 160ms; }
.reg-google-btn--active:hover:not(:disabled) { background: color-mix(in srgb, var(--border) 25%, var(--bg)); }
.reg-google-btn:disabled { opacity: 0.6; cursor: wait; }
.reg-google-icon { width: 18px; height: 18px; flex-shrink: 0; }

/* ── Divider ────────────────────────────────────────────────────────── */
.reg-divider {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  color: var(--divider-text);
  font-size: 11px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}
.reg-divider::before, .reg-divider::after { height: 1px; flex: 1; background: var(--divider); content: ''; }

/* ── Form ───────────────────────────────────────────────────────────── */
.reg-form { display: grid; gap: 14px; }

.reg-field { display: grid; gap: 6px; }
.reg-field label { font-size: 12.5px; font-weight: 600; color: var(--text); }
.reg-field input {
  width: 100%;
  height: 44px;
  padding: 0 14px;
  border: 1px solid var(--border);
  border-radius: 10px;
  outline: none;
  background: var(--input-bg);
  color: var(--input-text);
  font: inherit;
  font-size: 14px;
  box-sizing: border-box;
  transition: border-color 160ms, box-shadow 160ms;
}
.reg-field input::placeholder { color: var(--input-ph); }
.reg-field input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px var(--focus-ring); }
.reg-field input.is-error { border-color: var(--error); }
.reg-field input.is-error:focus { box-shadow: 0 0 0 3px color-mix(in srgb, var(--error) 15%, transparent); }
.reg-field input.is-ok { border-color: var(--ok); }

.reg-field-error { font-size: 11.5px; color: var(--error); }
.reg-field-ok { font-size: 11.5px; color: var(--ok); }

/* ── Password field ─────────────────────────────────────────────────── */
.reg-password-wrap { position: relative; }
.reg-password-wrap input { padding-right: 44px; }
.reg-eye-btn {
  position: absolute;
  top: 0; bottom: 0; right: 0;
  padding: 0 12px;
  display: flex;
  align-items: center;
  background: none;
  border: 0;
  color: var(--text-muted);
  cursor: pointer;
  transition: color 160ms;
}
.reg-eye-btn:hover { color: var(--text); }

/* ── Strength bar ───────────────────────────────────────────────────── */
.reg-strength { display: flex; align-items: center; gap: 10px; margin-top: 4px; }
.reg-strength-bars { display: flex; gap: 4px; flex: 1; }
.reg-strength-bar { height: 4px; flex: 1; border-radius: 99px; transition: background 300ms; }
.reg-strength span { font-size: 11px; font-weight: 500; white-space: nowrap; }

/* ── Turnstile ──────────────────────────────────────────────────────── */
.reg-turnstile-wrap { display: grid; gap: 6px; }
.reg-turnstile-label { font-size: 12.5px; font-weight: 600; color: var(--text); }
.reg-turnstile-widget { min-height: 65px; }

/* ── Terms ──────────────────────────────────────────────────────────── */
.reg-terms {
  margin: 0;
  font-size: 11px;
  line-height: 1.6;
  color: var(--terms-text);
}
.reg-terms a { color: var(--terms-link); text-decoration: underline; }

/* ── Submit ─────────────────────────────────────────────────────────── */
.reg-submit {
  width: 100%;
  height: 44px;
  border: 0;
  border-radius: 10px;
  background: var(--btn-bg);
  color: #fff;
  font: inherit;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 160ms;
}
.reg-submit:hover:not(:disabled) { background: var(--btn-hover); }
.reg-submit:disabled { opacity: 0.5; cursor: not-allowed; }

/* ── Footer ─────────────────────────────────────────────────────────── */
.reg-switch { margin-top: 20px; text-align: center; font-size: 12px; color: var(--text-muted); }
.reg-switch a { color: var(--link); font-weight: 600; text-decoration: none; }
.reg-switch a:hover { text-decoration: underline; }
</style>
