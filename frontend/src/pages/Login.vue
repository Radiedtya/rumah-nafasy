<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { ArrowRightIcon } from '@heroicons/vue/20/solid'
import { useTheme } from '../composables/theme'

const router = useRouter()
const auth = useAuthStore()
const { resolvedMode, setMode } = useTheme()

const email = ref('')
const password = ref('')
const errors = ref<{ email?: string; password?: string; form?: string }>({})
const isLoading = ref(false)

// ── Validation ──────────────────────────────────────────────────────────────

function validateEmail(value: string): string | null {
  if (!value.trim()) return 'Email wajib diisi'
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!re.test(value.trim())) return 'Format email tidak valid'
  return null
}

function validatePassword(value: string): string | null {
  if (!value) return 'Password wajib diisi'
  if (value.length < 8) return 'Password minimal 8 karakter'
  return null
}

function validate(): boolean {
  const errs: typeof errors.value = {}
  const emailErr = validateEmail(email.value)
  if (emailErr) errs.email = emailErr
  const pwErr = validatePassword(password.value)
  if (pwErr) errs.password = pwErr
  errors.value = errs
  return Object.keys(errs).length === 0
}

// ── Submit ───────────────────────────────────────────────────────────────────

async function handleLogin() {
  if (!validate()) return
  isLoading.value = true
  errors.value = {}
  try {
    await auth.login(email.value.trim(), password.value)
    router.push('/dashboard')
  } catch (err: any) {
    if (err.errors) {
      errors.value = {
        email: err.errors.email?.[0],
        password: err.errors.password?.[0],
        form: !err.errors.email && !err.errors.password ? err.message : undefined,
      }
    } else {
      errors.value = { form: err.message || 'Login gagal. Periksa kembali email dan password Anda.' }
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div :data-theme="resolvedMode" class="login-page">
    <section class="login-panel">
      <div class="login-form-wrap">
        <!-- Brand — klik kembali ke / -->
        <RouterLink to="/" class="login-brand" aria-label="Kembali ke beranda Rumah Natasy">
          <img src="/icon.svg" alt="" aria-hidden="true" />
          <span>Rumah Natasy</span>
        </RouterLink>

        <div class="login-heading">
          <h1>Selamat datang kembali!</h1>
          <p>Masuk untuk melanjutkan perjalanan kesehatan mental Anda.</p>
        </div>

        <!-- Google (placeholder — belum terhubung) -->
        <button type="button" class="google-button" disabled aria-label="Masuk dengan Google (segera hadir)">
          <svg class="google-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
          </svg>
          Masuk dengan Google
        </button>

        <div class="login-divider"><span>ATAU</span></div>

        <!-- Error global -->
        <div v-if="errors.form" class="login-error" role="alert">{{ errors.form }}</div>

        <form class="login-form" @submit.prevent="handleLogin" novalidate>
          <div class="field-group">
            <label for="login-email">Email address</label>
            <input
              id="login-email"
              v-model="email"
              type="email"
              autocomplete="email"
              placeholder="nama@email.com"
              :class="{ 'input-error': errors.email }"
              @blur="errors.email = validateEmail(email) ?? undefined"
            />
            <span v-if="errors.email" class="field-error" role="alert">{{ errors.email }}</span>
          </div>

          <div class="field-group">
            <label for="login-password">Password</label>
            <input
              id="login-password"
              v-model="password"
              type="password"
              autocomplete="current-password"
              placeholder="Masukkan password Anda"
              :class="{ 'input-error': errors.password }"
              @blur="errors.password = validatePassword(password) ?? undefined"
            />
            <span v-if="errors.password" class="field-error" role="alert">{{ errors.password }}</span>
          </div>

          <button type="submit" class="continue-button" :disabled="isLoading">
            <span>{{ isLoading ? 'Memverifikasi...' : 'Lanjutkan' }}</span>
            <ArrowRightIcon class="button-arrow" aria-hidden="true" />
          </button>
        </form>

        <p class="login-switch">
          Belum punya akun?
          <RouterLink to="/register">Daftar sekarang</RouterLink>
        </p>
        <p class="login-terms">
          Dengan masuk, Anda menyetujui<br />
          <a href="#">Kebijakan Privasi</a> dan <a href="#">Ketentuan Layanan</a>
        </p>

        <!-- Theme toggle -->
        <button
          type="button"
          class="theme-toggle"
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
      </div>
    </section>

    <section class="login-visual" aria-label="Visual platform Rumah Natasy">
      <img src="/images/assets/login.png" alt="Placeholder visual platform Rumah Natasy" />
    </section>
  </div>
</template>

<style scoped>
/* ── Tokens (light default) ─────────────────────────────────────────── */
.login-page {
  --bg: #ffffff;
  --text: #0d0e12;
  --text-muted: #737b8c;
  --border: #d9dde5;
  --divider: #d7dbe2;
  --divider-text: #747c8b;
  --input-bg: #ffffff;
  --input-text: #16171a;
  --input-placeholder: #aeb5c2;
  --input-focus-ring: rgb(22 136 237 / 12%);
  --error-bg: #fff0f0;
  --error-text: #c03939;
  --btn-bg: #1688ed;
  --btn-hover: #0876d8;
  --switch-text: #17191e;
  --terms-text: #7c8492;
  --terms-link: #252a32;
  --link: #1688ed;
  --google-border: #d9dde5;
  --google-text: #16171a;
  --toggle-text: #8992a2;
}
.login-page[data-theme='dark'] {
  --bg: #0f1117;
  --text: #f0f2f5;
  --text-muted: #8992a2;
  --border: #2a2d36;
  --divider: #2a2d36;
  --divider-text: #555d6e;
  --input-bg: #1a1d27;
  --input-text: #f0f2f5;
  --input-placeholder: #4a5263;
  --input-focus-ring: rgb(22 136 237 / 20%);
  --error-bg: #2a1a1a;
  --error-text: #f87171;
  --btn-bg: #1688ed;
  --btn-hover: #1e95ff;
  --switch-text: #c8cdd7;
  --terms-text: #555d6e;
  --terms-link: #9aa1b0;
  --link: #4da6ff;
  --google-border: #2a2d36;
  --google-text: #9aa1b0;
  --toggle-text: #555d6e;
}

/* ── Layout ─────────────────────────────────────────────────────────── */
.login-page {
  height: 100svh;
  min-height: 0;
  display: grid;
  grid-template-columns: minmax(420px, 1fr) minmax(480px, 1fr);
  overflow: hidden;
  background: var(--bg);
  color: var(--text);
  font-family: var(--font-body, system-ui, sans-serif);
}
.login-panel {
  position: relative;
  display: flex;
  justify-content: center;
  min-height: 0;
  overflow: hidden;
  padding: 30px 54px 26px;
  background: var(--bg);
}
.login-form-wrap {
  width: min(100%, 358px);
  display: flex;
  flex-direction: column;
}

/* ── Brand ──────────────────────────────────────────────────────────── */
.login-brand {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  width: fit-content;
  color: var(--text);
  font-size: 21px;
  font-weight: 800;
  letter-spacing: -0.06em;
  text-decoration: none;
}
.login-brand img { width: 28px; height: 28px; object-fit: contain; }

/* ── Heading ────────────────────────────────────────────────────────── */
.login-heading { margin-top: 58px; }
.login-heading h1 {
  margin: 0;
  font-size: 32px;
  line-height: 1.05;
  letter-spacing: -0.06em;
  font-weight: 700;
  color: var(--text);
}
.login-heading p { max-width: 320px; margin: 8px 0 0; color: var(--text-muted); font-size: 14px; line-height: 1.3; }

/* ── Google button ──────────────────────────────────────────────────── */
.google-button {
  width: 100%;
  height: 45px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 22px;
  border: 1px solid var(--google-border);
  border-radius: 4px;
  background: var(--bg);
  color: var(--google-text);
  font: inherit;
  font-size: 14px;
  font-weight: 600;
  cursor: not-allowed;
  opacity: 0.5;
}
.google-icon { width: 18px; height: 18px; flex-shrink: 0; }

/* ── Divider ────────────────────────────────────────────────────────── */
.login-divider {
  display: flex;
  align-items: center;
  gap: 17px;
  margin: 19px 0 18px;
  color: var(--divider-text);
  font-size: 12px;
}
.login-divider::before,
.login-divider::after { height: 1px; flex: 1; background: var(--divider); content: ''; }

/* ── Form ───────────────────────────────────────────────────────────── */
.login-form { display: grid; gap: 9px; }
.field-group { display: grid; gap: 7px; }
.field-group label { color: var(--text-muted); font-size: 13px; }
.field-group input {
  width: 100%;
  height: 45px;
  padding: 0 13px;
  border: 1px solid var(--border);
  border-radius: 4px;
  outline: none;
  background: var(--input-bg);
  color: var(--input-text);
  font: inherit;
  font-size: 14px;
  box-sizing: border-box;
  transition: border-color 160ms, box-shadow 160ms;
}
.field-group input::placeholder { color: var(--input-placeholder); }
.field-group input:focus { border-color: #1688ed; box-shadow: 0 0 0 3px var(--input-focus-ring); }
.field-group input.input-error { border-color: var(--error-text); }
.field-group input.input-error:focus { box-shadow: 0 0 0 3px rgb(192 57 57 / 12%); }
.field-error { font-size: 11.5px; color: var(--error-text); margin-top: -2px; }

/* ── Submit button ──────────────────────────────────────────────────── */
.continue-button {
  width: 100%;
  height: 45px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 14px;
  border: 0;
  border-radius: 4px;
  background: var(--btn-bg);
  color: #fff;
  font: inherit;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 160ms ease;
}
.continue-button:hover:not(:disabled) { background: var(--btn-hover); }
.continue-button:disabled { cursor: wait; opacity: 0.65; }
.button-arrow { width: 16px; }

/* ── Error box ──────────────────────────────────────────────────────── */
.login-error {
  margin-bottom: 14px;
  padding: 10px 12px;
  border-radius: 4px;
  background: var(--error-bg);
  color: var(--error-text);
  font-size: 12px;
}

/* ── Footer ─────────────────────────────────────────────────────────── */
.login-switch { margin: 12px 0 0; color: var(--switch-text); font-size: 12px; }
.login-switch a { color: var(--link); font-weight: 600; text-decoration: none; }
.login-terms { margin-top: auto; padding-top: 28px; color: var(--terms-text); font-size: 11px; line-height: 1.35; }
.login-terms a { color: var(--terms-link); font-weight: 700; text-decoration: none; }

/* ── Theme toggle ───────────────────────────────────────────────────── */
.theme-toggle {
  position: absolute;
  top: 18px;
  right: 18px;
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: transparent;
  color: var(--toggle-text);
  cursor: pointer;
  transition: background 160ms, color 160ms;
}
.theme-toggle svg { width: 16px; height: 16px; }
.theme-toggle:hover { background: var(--border); color: var(--text); }

/* ── Visual panel ───────────────────────────────────────────────────── */
.login-visual { min-height: 100svh; overflow: hidden; background: #073b77; }
.login-visual img { display: block; width: 100%; height: 100%; object-fit: cover; }

/* ── Responsive ─────────────────────────────────────────────────────── */
@media (max-width: 800px) {
  .login-page { display: block; }
  .login-panel { height: 100svh; padding: 24px; }
  .login-heading { margin-top: 48px; }
  .login-visual { display: none; }
  .login-terms { padding-top: 24px; }
}
@media (max-height: 700px) and (min-width: 801px) {
  .login-panel { padding-top: 22px; padding-bottom: 18px; }
  .login-heading { margin-top: 34px; }
  .login-heading h1 { font-size: 29px; }
  .google-button { margin-top: 16px; }
  .login-divider { margin-top: 14px; margin-bottom: 13px; }
  .login-terms { padding-top: 16px; }
}
</style>
