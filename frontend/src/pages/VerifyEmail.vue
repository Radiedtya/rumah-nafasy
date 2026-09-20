<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useTheme } from '../composables/theme'

/**
 * ── Verifikasi Email via OTP (dikirim Resend) ──────────────────────────
 * 6 kotak kode, auto-advance, paste, resend dengan countdown cooldown.
 * Token API diterbitkan server SETELAH kode benar → langsung masuk.
 */

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const { resolvedMode, setMode } = useTheme()

const email = ref(typeof route.query.email === 'string' ? route.query.email : '')
// Pengenal sesi OTP dari halaman register — wajib untuk pendaftar baru
// (yang hanya tahu email tidak bisa memverifikasi akun orang lain).
const verifyHandle = ref(typeof route.query.vh === 'string' ? route.query.vh : '')

// ── 6 kotak OTP ──────────────────────────────────────────────────────────────
const DIGITS = 6
const boxes = ref<string[]>(Array(DIGITS).fill(''))
const inputs = ref<HTMLInputElement[]>([])
const focusedIndex = ref(0)

const code = computed(() => boxes.value.join(''))
const isComplete = computed(() => code.value.length === DIGITS && boxes.value.every(d => d !== ''))

// ── State ────────────────────────────────────────────────────────────────────
const isLoading = ref(false)
const isResending = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const attemptsLeft = ref<number | null>(null)
const cooldown = ref(0)
const canResend = computed(() => cooldown.value <= 0 && !isResending.value)
const maskedEmail = computed(() => {
  const [local, domain] = email.value.split('@')
  if (!local || !domain) return email.value
  return `${local[0]}${'*'.repeat(Math.max(3, local.length - 1))}@${domain}`
})

let cooldownTimer: ReturnType<typeof setInterval> | null = null

function startCooldown(seconds: number) {
  cooldown.value = Math.max(0, Math.floor(seconds))
  if (cooldownTimer) clearInterval(cooldownTimer)
  cooldownTimer = setInterval(() => {
    if (cooldown.value <= 0) {
      if (cooldownTimer) clearInterval(cooldownTimer)
      cooldownTimer = null
      return
    }
    cooldown.value--
  }, 1000)
}

const cooldownLabel = computed(() => {
  const m = Math.floor(cooldown.value / 60)
  const s = cooldown.value % 60
  return m > 0 ? `${m}:${String(s).padStart(2, '0')}` : `${s}s`
})

// ── Input handlers ───────────────────────────────────────────────────────────
function focusBox(i: number) {
  nextTick(() => inputs.value[i]?.focus())
}

function onInput(i: number, e: Event) {
  const el = e.target as HTMLInputElement
  const digit = el.value.replace(/\D/g, '').slice(-1)

  boxes.value[i] = digit
  el.value = digit
  errorMessage.value = ''

  if (digit && i < DIGITS - 1) focusBox(i + 1)
  if (isComplete.value) submit()
}

function onKeyDown(i: number, e: KeyboardEvent) {
  if (e.key === 'Backspace') {
    if (!boxes.value[i] && i > 0) {
      focusBox(i - 1)
      boxes.value[i - 1] = ''
    } else {
      boxes.value[i] = ''
    }
    errorMessage.value = ''
  } else if (e.key === 'ArrowLeft' && i > 0) {
    focusBox(i - 1)
  } else if (e.key === 'ArrowRight' && i < DIGITS - 1) {
    focusBox(i + 1)
  }
}

function onPaste(e: ClipboardEvent) {
  e.preventDefault()
  const text = (e.clipboardData?.getData('text') ?? '').replace(/\D/g, '').slice(0, DIGITS)
  if (!text) return

  for (let i = 0; i < DIGITS; i++) {
    boxes.value[i] = text[i] ?? ''
  }
  focusBox(Math.min(text.length, DIGITS - 1))

  if (text.length === DIGITS) submit()
}

// ── Submit verifikasi ────────────────────────────────────────────────────────
async function submit() {
  if (!isComplete.value || isLoading.value) return

  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await auth.verifyEmail(email.value, code.value, verifyHandle.value)
    successMessage.value = 'Email terverifikasi! Mengalihkan ke dashboard…'
    setTimeout(() => router.push('/dashboard'), 900)
  } catch (err: any) {
    // Reset kotak supaya user ketik ulang
    boxes.value = Array(DIGITS).fill('')
    focusBox(0)

    attemptsLeft.value = typeof err?.errors?.attempts_left?.[0] === 'number'
      ? err.errors.attempts_left[0]
      : null

    errorMessage.value = err?.message || 'Kode verifikasi tidak valid atau kedaluwarsa.'
  } finally {
    isLoading.value = false
  }
}

// ── Resend ───────────────────────────────────────────────────────────────────
async function resend() {
  if (!canResend.value) return

  isResending.value = true
  errorMessage.value = ''

  try {
    const result = await auth.resendEmailOtp(email.value, verifyHandle.value)
    successMessage.value = 'Kode baru telah dikirim ke email Anda.'
    boxes.value = Array(DIGITS).fill('')
    focusBox(0)
    startCooldown(result.retry_after ?? 60)
    setTimeout(() => { successMessage.value = '' }, 4000)
  } catch (err: any) {
    const retry = err?.errors?.retry_after?.[0]
    if (err?.status === 429) {
      startCooldown(typeof retry === 'number' ? retry : 60)
      errorMessage.value = err.message || 'Terlalu sering meminta kode. Tunggu sebentar.'
    } else {
      errorMessage.value = err?.message || 'Gagal mengirim ulang kode.'
    }
  } finally {
    isResending.value = false
  }
}

// ── Init: sinkron cooldown dari server ───────────────────────────────────────
onMounted(async () => {
  if (!email.value) {
    router.replace('/login')
    return
  }

  focusBox(0)

  try {
    const status = await auth.emailStatus(email.value, verifyHandle.value)
    if (status.verified) {
      // Sudah verifikasi — tidak ada alasan di halaman ini.
      router.replace('/login')
      return
    }
    if (status.retry_after && status.retry_after > 0) {
      startCooldown(status.retry_after)
    }
  } catch {
    // Status tidak kritis — abaikan.
  }
})

onUnmounted(() => {
  if (cooldownTimer) clearInterval(cooldownTimer)
})
</script>

<template>
  <div :data-theme="resolvedMode" class="ve-page">
    <!-- Theme toggle -->
    <button
      type="button"
      class="ve-theme-toggle"
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

    <div class="ve-inner">
      <div class="ve-header">
        <RouterLink to="/" class="ve-brand" aria-label="Kembali ke beranda Rumah Natasy">
          <img src="/icon.svg" alt="" aria-hidden="true" />
          Rumah Natasy
        </RouterLink>
        <h1 class="ve-heading">Verifikasi Email Anda</h1>
        <p class="ve-sub">
          Kami mengirim kode 6 digit ke<br />
          <strong>{{ maskedEmail }}</strong>
        </p>
      </div>

      <!-- Error -->
      <div v-if="errorMessage" class="ve-alert ve-alert--error" role="alert">
        {{ errorMessage }}
        <span v-if="attemptsLeft !== null && attemptsLeft > 0" class="ve-attempts">
          Sisa percobaan: {{ attemptsLeft }}
        </span>
      </div>

      <!-- Success -->
      <div v-if="successMessage" class="ve-alert ve-alert--ok" role="status">{{ successMessage }}</div>

      <!-- Kotak OTP -->
      <form class="ve-form" @submit.prevent="submit" novalidate>
        <div class="ve-boxes" role="group" aria-label="Kode verifikasi 6 digit">
          <input
            v-for="(digit, i) in boxes"
            :key="i"
            :ref="(el) => { if (el) inputs[i] = el as HTMLInputElement }"
            v-model="boxes[i]"
            type="text"
            inputmode="numeric"
            autocomplete="one-time-code"
            maxlength="1"
            class="ve-box"
            :class="{ 've-box--filled': !!digit, 've-box--error': !!errorMessage }"
            :aria-label="`Digit ${i + 1}`"
            @input="onInput(i, $event)"
            @keydown="onKeyDown(i, $event)"
            @paste="onPaste"
            @focus="focusedIndex = i"
          />
        </div>

        <button type="submit" class="ve-submit" :disabled="!isComplete || isLoading">
          <span v-if="isLoading" class="ve-spinner" aria-hidden="true"></span>
          {{ isLoading ? 'Memverifikasi…' : 'Verifikasi Sekarang' }}
        </button>
      </form>

      <!-- Resend -->
      <div class="ve-resend">
        <template v-if="canResend">
          <span>Tidak menerima kode?</span>
          <button type="button" class="ve-resend-btn" :disabled="isResending" @click="resend">
            {{ isResending ? 'Mengirim…' : 'Kirim ulang kode' }}
          </button>
        </template>
        <span v-else class="ve-cooldown">Kirim ulang tersedia dalam <strong>{{ cooldownLabel }}</strong></span>
      </div>

      <p class="ve-footer">
        Salah email?
        <RouterLink to="/register">Daftar ulang</RouterLink>
      </p>
    </div>
  </div>
</template>

<style scoped>
/* ── Tokens (mengikuti gaya Register.vue) ──────────────────────────── */
.ve-page {
  --bg: #ffffff;
  --text: #111318;
  --text-muted: #6b7280;
  --border: #d1d5db;
  --input-bg: #ffffff;
  --focus-ring: rgb(37 99 235 / 15%);
  --error: #dc2626;
  --error-bg: #fef2f2;
  --ok: #16a34a;
  --ok-bg: #f0fdf4;
  --btn-bg: #2563eb;
  --btn-hover: #1d4ed8;
  --link: #2563eb;
  --toggle-border: #d1d5db;
  --toggle-text: #6b7280;
}
.ve-page[data-theme='dark'] {
  --bg: #0f1117;
  --text: #f0f2f5;
  --text-muted: #8992a2;
  --border: #272b36;
  --input-bg: #191c27;
  --focus-ring: rgb(59 130 246 / 20%);
  --error: #f87171;
  --error-bg: #2a1a1a;
  --ok: #4ade80;
  --ok-bg: #12291a;
  --btn-bg: #2563eb;
  --btn-hover: #3b82f6;
  --link: #60a5fa;
  --toggle-border: #272b36;
  --toggle-text: #555d6e;
}

.ve-page {
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

.ve-theme-toggle {
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
  transition: background 160ms, color 160ms;
  z-index: 10;
}
.ve-theme-toggle svg { width: 16px; height: 16px; }
.ve-theme-toggle:hover { background: var(--border); color: var(--text); }

.ve-inner { width: 100%; max-width: 420px; }

.ve-header { margin-bottom: 26px; text-align: center; }
.ve-brand {
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
.ve-brand img { width: 26px; height: 26px; object-fit: contain; }
.ve-heading {
  margin: 0 0 8px;
  font-size: 26px;
  font-weight: 700;
  letter-spacing: -0.04em;
  line-height: 1.1;
  color: var(--text);
}
.ve-sub { margin: 0; font-size: 13.5px; color: var(--text-muted); line-height: 1.55; }
.ve-sub strong { color: var(--text); font-weight: 600; }

.ve-alert {
  margin-bottom: 16px;
  padding: 10px 14px;
  border-radius: 10px;
  font-size: 12.5px;
  border: 1px solid transparent;
}
.ve-alert--error {
  background: var(--error-bg);
  color: var(--error);
  border-color: color-mix(in srgb, var(--error) 20%, transparent);
}
.ve-alert--ok {
  background: var(--ok-bg);
  color: var(--ok);
  border-color: color-mix(in srgb, var(--ok) 20%, transparent);
}
.ve-attempts { display: block; margin-top: 4px; font-size: 11px; opacity: 0.85; }

.ve-form { display: grid; gap: 18px; }

.ve-boxes {
  display: flex;
  gap: 10px;
  justify-content: center;
}
.ve-box {
  width: 48px;
  height: 56px;
  border: 1.5px solid var(--border);
  border-radius: 12px;
  outline: none;
  background: var(--input-bg);
  color: var(--text);
  font-size: 24px;
  font-weight: 700;
  text-align: center;
  font-variant-numeric: tabular-nums;
  transition: border-color 160ms, box-shadow 160ms, transform 100ms;
  caret-color: var(--btn-bg);
}
.ve-box:focus {
  border-color: var(--btn-bg);
  box-shadow: 0 0 0 3px var(--focus-ring);
  transform: translateY(-1px);
}
.ve-box--filled { border-color: var(--btn-bg); }
.ve-box--error { border-color: var(--error); }

.ve-submit {
  width: 100%;
  height: 46px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border: 0;
  border-radius: 10px;
  background: var(--btn-bg);
  color: #fff;
  font: inherit;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 160ms, opacity 160ms;
}
.ve-submit:hover:not(:disabled) { background: var(--btn-hover); }
.ve-submit:disabled { opacity: 0.5; cursor: not-allowed; }

.ve-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgb(255 255 255 / 35%);
  border-top-color: #fff;
  border-radius: 50%;
  animation: ve-spin 700ms linear infinite;
}
@keyframes ve-spin { to { transform: rotate(360deg); } }

.ve-resend {
  margin-top: 22px;
  text-align: center;
  font-size: 12.5px;
  color: var(--text-muted);
}
.ve-resend-btn {
  border: 0;
  background: none;
  color: var(--link);
  font: inherit;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  margin-left: 6px;
}
.ve-resend-btn:hover:not(:disabled) { text-decoration: underline; }
.ve-resend-btn:disabled { opacity: 0.5; cursor: wait; }
.ve-cooldown strong { color: var(--text); font-variant-numeric: tabular-nums; }

.ve-footer {
  margin-top: 28px;
  text-align: center;
  font-size: 12px;
  color: var(--text-muted);
}
.ve-footer a { color: var(--link); font-weight: 600; text-decoration: none; }
.ve-footer a:hover { text-decoration: underline; }

@media (max-width: 400px) {
  .ve-boxes { gap: 7px; }
  .ve-box { width: 42px; height: 50px; font-size: 21px; }
}
</style>
