<script setup lang="ts">
import { ref, computed } from 'vue'
import { apiFetch } from '../../lib/api'
import { useAuthStore } from '../../stores/auth'
import type { UserProfile } from '../../stores/auth'
import { useGooglePopup } from '../../composables/googleAuth'
import { BACKEND_URL } from '../../lib/config'
import BaseButton from '../../components/ui/BaseButton.vue'
import {
  ShieldCheckIcon,
  ExclamationTriangleIcon,
  LinkIcon,
  TrashIcon,
  KeyIcon,
} from '@heroicons/vue/24/outline'

/**
 * Metode Masuk — kelola Google & password dari halaman Profil.
 *
 * - Hubungkan Google: minta URL connect ber-token dari API (auth:sanctum)
 *   → popup → backend menautkan google_id ke user yang login.
 * - Putuskan Google: wajib konfirmasi password (server menolak tanpa itu).
 * - Ubah/Atur password: endpoint khusus PUT auth/password.
 *     • User ber-password → wajib password lama.
 *     • Akun Google murni → wajib OTP email (Resend) yang dikirim server.
 */

const auth = useAuthStore()
const { open: openGooglePopup, spaCallbackUrl } = useGooglePopup()

const message = ref('')
const error = ref('')

const hasGoogle = computed(() => auth.user?.has_google ?? false)
const hasPassword = computed(() => auth.user?.has_password ?? true)

// ── Hubungkan Google (connect flow via popup ber-token) ─────────────────────
const connecting = ref(false)

async function handleConnectGoogle() {
  message.value = ''
  error.value = ''
  connecting.value = true

  try {
    // Token one-time diterbitkan endpoint terautentikasi — popup membawa
    // token itu; backend menautkan ke user yang sedang login saja.
    const res = await apiFetch<{ url: string }>('auth/google/connect/start', {
      method: 'POST',
      body: JSON.stringify({ landing: spaCallbackUrl() }),
    })

    const ok = openGooglePopup(
      res.data.url.replace(/^http:\/\/localhost:8000/, BACKEND_URL),
      {
        onCode: (code) => finishConnect(code),
        onError: (kind) => {
          connecting.value = false
          if (kind === 'conflict') {
            error.value = 'Email akun Google ini sudah dipakai user lain — hubungi dukungan.'
          } else if (kind === 'linked') {
            error.value = 'Akun Google ini sudah terhubung ke akun lain.'
          } else if (kind === 'token') {
            error.value = 'Sesi penghubungan kedaluwarsa — klik hubungkan lagi.'
          } else {
            error.value = 'Gagal menghubungkan Google — coba lagi.'
          }
        },
        onDismissed: () => {
          connecting.value = false
        },
      },
    )

    if (!ok) {
      // Popup diblokir → fallback navigasi penuh (tetap membawa token one-time)
      window.location.href = res.data.url.replace(/^http:\/\/localhost:8000/, BACKEND_URL)
    }
  } catch (err: any) {
    connecting.value = false
    error.value = err.message || 'Gagal menyiapkan penghubungan Google'
  }
}

async function finishConnect(code: string) {
  try {
    await apiFetch('auth/google/exchange', {
      method: 'POST',
      body: JSON.stringify({ code }),
    })
    await auth.fetchMe()
    message.value = 'Akun Google berhasil terhubung!'
  } catch (err: any) {
    error.value = err.message || 'Gagal menyelesaikan penghubungan'
  } finally {
    connecting.value = false
  }
}

// ── Putuskan Google ──────────────────────────────────────────────────────────
const disconnectOpen = ref(false)
const disconnectPassword = ref('')
const disconnecting = ref(false)

async function submitDisconnect() {
  disconnecting.value = true
  error.value = ''
  try {
    const res = await apiFetch<UserProfile>('auth/google/disconnect', {
      method: 'POST',
      body: JSON.stringify({ password: disconnectPassword.value }),
    })
    auth.user = res.data
    if (typeof window !== 'undefined') {
      localStorage.setItem('user', JSON.stringify(res.data))
    }
    disconnectOpen.value = false
    disconnectPassword.value = ''
    message.value = 'Akun Google berhasil diputus.'
  } catch (err: any) {
    error.value = err.message || 'Gagal memutus akun Google'
  } finally {
    disconnecting.value = false
  }
}

// ── Ubah / Atur password (endpoint khusus PUT auth/password) ─────────────────
const passwordOpen = ref(false)
const passwordError = ref('')
const passwordSuccess = ref('')

// Form umum
const newPassword = ref('')
const confirmNewPassword = ref('')
const savingPassword = ref(false)

// Jalur user ber-password
const currentPassword = ref('')

// Jalur akun Google murni (OTP email)
const otpStep = ref<'idle' | 'code-sent'>('idle')
const otpCode = ref('')
const otpToken = ref('')
const sendingOtp = ref(false)
const otpCooldown = ref(0)
let otpCooldownTimer: ReturnType<typeof setInterval> | null = null

const isSetMode = computed(() => !hasPassword.value)

function openPasswordModal() {
  passwordOpen.value = true
  passwordError.value = ''
  passwordSuccess.value = ''
  currentPassword.value = ''
  newPassword.value = ''
  confirmNewPassword.value = ''
  otpCode.value = ''
  otpToken.value = ''
  otpStep.value = 'idle'
}

function closePasswordModal() {
  passwordOpen.value = false
  passwordError.value = ''
  passwordSuccess.value = ''
  if (otpCooldownTimer) {
    clearInterval(otpCooldownTimer)
    otpCooldownTimer = null
  }
}

function validateNewPassword(): boolean {
  const v = newPassword.value
  if (!v) { passwordError.value = 'Password baru wajib diisi'; return false }
  if (v.length < 8) { passwordError.value = 'Password minimal 8 karakter'; return false }
  if (!/[A-Z]/.test(v)) { passwordError.value = 'Harus mengandung 1 huruf kapital'; return false }
  if (!/[a-z]/.test(v)) { passwordError.value = 'Harus mengandung 1 huruf kecil'; return false }
  if (!/[0-9]/.test(v)) { passwordError.value = 'Harus mengandung 1 angka'; return false }
  if (v !== confirmNewPassword.value) { passwordError.value = 'Konfirmasi tidak cocok'; return false }

  if (!isSetMode.value && !currentPassword.value) {
    passwordError.value = 'Password saat ini wajib diisi'
    return false
  }
  if (isSetMode.value && otpStep.value !== 'code-sent') {
    passwordError.value = 'Minta kode verifikasi email terlebih dahulu'
    return false
  }
  if (isSetMode.value && otpCode.value.replace(/\D/g, '').length !== 6) {
    passwordError.value = 'Kode verifikasi harus 6 digit'
    return false
  }

  passwordError.value = ''
  return true
}

/** Akun Google murni: minta OTP email → server mengirim kode via Resend. */
async function requestSetPasswordOtp() {
  sendingOtp.value = true
  passwordError.value = ''
  try {
    const res = await auth.sendSetPasswordOtp()
    otpToken.value = res.otp_token
    otpStep.value = 'code-sent'
    passwordSuccess.value = `Kode verifikasi dikirim ke ${res.email}.`
    startOtpCooldown(res.retry_after ?? 60)
  } catch (err: any) {
    const retry = err?.errors?.retry_after?.[0]
    if (err?.status === 429 && typeof retry === 'number') {
      startOtpCooldown(retry)
    }
    passwordError.value = err?.message || 'Gagal mengirim kode verifikasi.'
  } finally {
    sendingOtp.value = false
  }
}

function startOtpCooldown(seconds: number) {
  otpCooldown.value = Math.max(0, Math.floor(seconds))
  if (otpCooldownTimer) clearInterval(otpCooldownTimer)
  otpCooldownTimer = setInterval(() => {
    if (otpCooldown.value <= 0) {
      if (otpCooldownTimer) clearInterval(otpCooldownTimer)
      otpCooldownTimer = null
      return
    }
    otpCooldown.value--
  }, 1000)
}

async function submitPassword() {
  if (!validateNewPassword()) return

  savingPassword.value = true
  passwordError.value = ''
  passwordSuccess.value = ''

  try {
    if (isSetMode.value) {
      // Akun Google murni — set password pertama kali, dijaga OTP email
      await auth.updatePassword({
        otp_token: otpToken.value,
        otp_code: otpCode.value.replace(/\D/g, ''),
        password: newPassword.value,
        password_confirmation: confirmNewPassword.value,
      })
    } else {
      // User ber-password — dijaga password lama
      await auth.updatePassword({
        current_password: currentPassword.value,
        password: newPassword.value,
        password_confirmation: confirmNewPassword.value,
      })
    }

    await auth.fetchMe()
    message.value = isSetMode.value
      ? 'Password berhasil diatur — kini bisa login email + password.'
      : 'Password berhasil diubah. Sesi di perangkat lain telah dikeluarkan.'

    closePasswordModal()
  } catch (err: any) {
    passwordError.value = err?.errors
      ? (Object.values(err.errors).flat()[0] as string) || err.message
      : (err?.message || 'Gagal menyimpan password')
  } finally {
    savingPassword.value = false
  }
}
</script>

<template>
  <BaseCard class="space-y-5">
    <div class="flex items-center gap-2">
      <ShieldCheckIcon class="h-4.5 w-4.5 text-[var(--accent)]" />
      <h2 class="text-sm font-semibold text-[var(--text)]">Metode Masuk</h2>
    </div>

    <div v-if="message" class="rounded-xl bg-emerald-500/10 px-3.5 py-2.5 text-xs font-medium text-emerald-600 dark:text-emerald-400">
      {{ message }}
    </div>
    <div v-if="error" class="rounded-xl bg-rose-500/10 px-3.5 py-2.5 text-xs text-rose-600 dark:text-rose-400">
      {{ error }}
    </div>

    <!-- Google -->
    <div class="flex flex-col justify-between gap-3 rounded-xl border border-[var(--line)] p-4 sm:flex-row sm:items-center">
      <div class="flex min-w-0 items-center gap-3">
        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
        </svg>
        <div class="min-w-0">
          <p class="text-xs font-semibold text-[var(--text)]">Google</p>
          <p class="text-[11px] text-[var(--muted)]">
            {{ hasGoogle ? 'Terhubung — bisa masuk satu klik' : 'Belum terhubung' }}
          </p>
        </div>
      </div>

      <BaseButton
        v-if="!hasGoogle"
        size="sm"
        :disabled="connecting"
        @click="handleConnectGoogle"
      >
        <LinkIcon class="h-3.5 w-3.5" />
        {{ connecting ? 'Menyiapkan…' : 'Hubungkan Google' }}
      </BaseButton>
      <BaseButton
        v-else
        variant="ghost"
        size="sm"
        class="!text-rose-600 dark:!text-rose-400 hover:!bg-rose-500/10"
        :disabled="!hasPassword && hasGoogle"
        @click="disconnectOpen = true"
      >
        <TrashIcon class="h-3.5 w-3.5" />
        Putuskan
      </BaseButton>
    </div>

    <p v-if="hasGoogle && !hasPassword" class="flex items-start gap-2 rounded-xl bg-amber-500/8 px-3.5 py-2.5 text-[11px] leading-relaxed text-amber-700 dark:text-amber-300">
      <ExclamationTriangleIcon class="mt-0.5 h-3.5 w-3.5 shrink-0" />
      Akun ini hanya bisa masuk lewat Google. Atur password dulu sebelum memutus Google —
      <button type="button" class="font-semibold underline" @click="openPasswordModal">atur sekarang</button>
    </p>

    <!-- Password -->
    <div class="flex flex-col justify-between gap-3 rounded-xl border border-[var(--line)] p-4 sm:flex-row sm:items-center">
      <div class="flex min-w-0 items-center gap-3">
        <KeyIcon class="h-5 w-5 shrink-0 text-[var(--accent)]" />
        <div class="min-w-0">
          <p class="text-xs font-semibold text-[var(--text)]">Password</p>
          <p class="text-[11px] text-[var(--muted)]">
            {{ hasPassword ? 'Aktif — login email + password tersedia' : 'Belum diatur (login via Google saja)' }}
          </p>
        </div>
      </div>
      <BaseButton size="sm" variant="secondary" @click="openPasswordModal">
        {{ hasPassword ? 'Ubah Password' : 'Atur Password' }}
      </BaseButton>
    </div>

    <!-- Modal: putuskan Google -->
    <transition name="fade">
      <div v-if="disconnectOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-sm rounded-2xl border border-[var(--line)] bg-[var(--surface)] p-6 shadow-2xl">
          <h3 class="mb-2 text-sm font-semibold text-[var(--text)]">Putuskan akun Google?</h3>
          <p class="mb-4 text-xs leading-relaxed text-[var(--muted)]">
            Anda tidak akan bisa masuk lewat Google lagi. Konfirmasi dengan password untuk melanjutkan.
          </p>
          <input
            v-model="disconnectPassword"
            type="password"
            autocomplete="current-password"
            placeholder="Password Anda"
            class="field-input mb-3"
            @keyup.enter="submitDisconnect"
          />
          <div class="flex gap-3">
            <button
              type="button"
              class="h-9 flex-1 rounded-lg border border-[var(--line)] text-xs font-medium text-[var(--text)] transition-colors hover:bg-[var(--muted)]/10"
              @click="disconnectOpen = false"
            >Batal</button>
            <button
              type="button"
              class="h-9 flex-1 rounded-lg bg-rose-500 text-xs font-medium text-white transition-colors hover:bg-rose-600 disabled:opacity-50"
              :disabled="disconnecting || !disconnectPassword"
              @click="submitDisconnect"
            >{{ disconnecting ? 'Memproses…' : 'Putuskan' }}</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Modal: ubah / atur password -->
    <transition name="fade">
      <div v-if="passwordOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-sm rounded-2xl border border-[var(--line)] bg-[var(--surface)] p-6 shadow-2xl">
          <h3 class="mb-2 text-sm font-semibold text-[var(--text)]">
            {{ isSetMode ? 'Atur Password' : 'Ubah Password' }}
          </h3>
          <p class="mb-4 text-xs leading-relaxed text-[var(--muted)]">
            <template v-if="isSetMode">
              Akun ini masuk lewat Google. Untuk bisa login dengan email + password, atur password —
              kami kirim kode verifikasi ke email Anda untuk menjaga keamanan.
            </template>
            <template v-else>
              Setelah diubah, sesi di perangkat lain akan otomatis dikeluarkan.
            </template>
          </p>

          <div class="space-y-2">
            <!-- Jalur: user ber-password -->
            <input
              v-if="!isSetMode"
              v-model="currentPassword"
              type="password"
              autocomplete="current-password"
              placeholder="Password saat ini"
              class="field-input"
            />

            <!-- Jalur: akun Google murni — OTP email -->
            <template v-if="isSetMode">
              <div v-if="otpStep !== 'code-sent'" class="rounded-lg bg-[var(--muted)]/8 p-3">
                <button
                  type="button"
                  class="h-9 w-full rounded-lg bg-[var(--accent)] text-xs font-semibold text-white transition-opacity hover:opacity-90 disabled:opacity-50"
                  :disabled="sendingOtp || otpCooldown > 0"
                  @click="requestSetPasswordOtp"
                >
                  {{ sendingOtp
                    ? 'Mengirim…'
                    : otpCooldown > 0
                      ? `Tunggu ${otpCooldown}s…`
                      : 'Kirim Kode Verifikasi ke Email' }}
                </button>
              </div>
              <input
                v-else
                v-model="otpCode"
                type="text"
                inputmode="numeric"
                maxlength="6"
                autocomplete="one-time-code"
                placeholder="Kode 6 digit dari email"
                class="field-input field-input--otp"
              />
              <p v-if="otpStep === 'code-sent' && otpCooldown <= 0" class="text-[10.5px] text-[var(--muted)]">
                Tidak menerima kode?
                <button type="button" class="font-semibold text-[var(--accent)] hover:underline" @click="requestSetPasswordOtp">
                  Kirim ulang
                </button>
              </p>
            </template>

            <input
              v-model="newPassword"
              type="password"
              autocomplete="new-password"
              placeholder="Password baru (min. 8, kapital + kecil + angka)"
              class="field-input"
            />
            <input
              v-model="confirmNewPassword"
              type="password"
              autocomplete="new-password"
              placeholder="Ulangi password baru"
              class="field-input"
              @keyup.enter="submitPassword"
            />
            <p v-if="passwordError" class="text-[11px] text-rose-500">{{ passwordError }}</p>
            <p v-if="passwordSuccess" class="text-[11px] text-emerald-500">{{ passwordSuccess }}</p>
          </div>

          <div class="mt-4 flex gap-3">
            <button
              type="button"
              class="h-9 flex-1 rounded-lg border border-[var(--line)] text-xs font-medium text-[var(--text)] transition-colors hover:bg-[var(--muted)]/10"
              @click="closePasswordModal"
            >Batal</button>
            <button
              type="button"
              class="h-9 flex-1 rounded-lg bg-[var(--accent)] text-xs font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-50"
              :disabled="savingPassword || (isSetMode && otpStep !== 'code-sent')"
              @click="submitPassword"
            >{{ savingPassword ? 'Menyimpan…' : 'Simpan Password' }}</button>
          </div>
        </div>
      </div>
    </transition>
  </BaseCard>
</template>

<style scoped>
.field-input {
  width: 100%;
  height: 40px;
  padding: 0 12px;
  border: 1px solid var(--line);
  border-radius: 10px;
  outline: none;
  background: var(--surface);
  color: var(--text);
  font: inherit;
  font-size: 13px;
  box-sizing: border-box;
}
.field-input:focus { border-color: var(--accent); }
.field-input--otp {
  text-align: center;
  font-size: 18px;
  font-weight: 700;
  letter-spacing: 0.4em;
  font-variant-numeric: tabular-nums;
}

.fade-enter-active, .fade-leave-active { transition: opacity 200ms; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
