<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { apiFetch } from '../../lib/api'
import { useAuthStore } from '../../stores/auth'
import type { UserProfile } from '../../stores/auth'

/**
 * Callback Google di sisi SPA — dua mode:
 *
 * 1. POPUP (dibuka tombol "Masuk dengan Google" → ?popup=1):
 *    meneruskan kode ke jendela pembuka via postMessage (same-origin,
 *    hanya jika opener benar-benar ada) lalu menutup dirinya.
 *    Penukaran token dilakukan jendela induk.
 *
 * 2. STANDALONE (redirect penuh / fallback popup diblokir):
 *    menukar kode langsung via API, lalu masuk dashboard.
 */

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const status = ref<'working' | 'closing' | 'error'>('working')
const errorMessage = ref('')

/** Kirim hasil ke jendela pembuka — hanya same-origin & opener nyata. */
function sendToOpener(payload: { code?: string; error?: string }): boolean {
  // Tanpa opener → bukan popup sungguhan (mis. dibuka manual): jangan kirim apa pun
  if (!window.opener || window.opener === window) return false

  try {
    // targetOrigin eksplisit = origin kita sendiri; browser memblokir bila
    // opener ternyata origin lain — itu perilaku keamanan yang diinginkan.
    window.opener.postMessage({ type: 'google-oauth', ...payload }, window.location.origin)
    return true
  } catch {
    return false
  }
}

onMounted(async () => {
  const code = typeof route.query.code === 'string' ? route.query.code : ''
  const googleError = typeof route.query.google === 'string' ? route.query.google : ''
  const isPopup = route.query.popup === '1'

  // ── Mode popup: teruskan kode/error ke opener lalu tutup diri ──
  if (isPopup) {
    if (googleError) {
      if (sendToOpener({ error: googleError })) {
        status.value = 'closing'
        window.close()
        return
      }
      // Opener tidak ada → jatuh ke jalur standalone di bawah
    } else if (code) {
      if (sendToOpener({ code })) {
        status.value = 'closing'
        window.close()
        return
      }
      // Opener tidak ada → lanjut tukar token di jendela ini (fallback)
    } else {
      status.value = 'error'
      errorMessage.value = 'Kode login tidak ditemukan. Silakan coba lagi dari halaman masuk.'
      return
    }
  }

  // ── Mode standalone: tukar kode langsung ──
  if (!code) {
    status.value = 'error'
    errorMessage.value =
      googleError === 'blocked'
        ? 'Akun Anda dinonaktifkan — hubungi admin Rumah Nafasy.'
        : googleError === 'error'
          ? 'Login Google gagal — akun Google tidak memberikan email atau terjadi kesalahan.'
          : 'Kode login tidak ditemukan. Silakan coba lagi dari halaman masuk.'
    return
  }

  try {
    const res = await apiFetch<{ user: UserProfile; token: string }>('auth/google/exchange', {
      method: 'POST',
      body: JSON.stringify({ code }),
    })
    auth.setSession(res.data.token, res.data.user)

    // Redirect ke tujuan semula (dibawa di query) — default dashboard
    const raw = typeof route.query.redirect === 'string' ? route.query.redirect : '/dashboard'
    const redirect = raw.startsWith('/') && !raw.startsWith('//') && !raw.includes('\\') ? raw : '/dashboard'
    router.replace(redirect)
  } catch (err: any) {
    status.value = 'error'
    errorMessage.value = err.message || 'Gagal menyelesaikan login Google.'
  }
})

function backToLogin() {
  router.replace('/login')
}
</script>

<template>
  <div class="min-h-svh bg-[var(--bg,#0f1117)] text-[var(--text,#f0f2f5)]" data-theme="dark">
    <div class="mx-auto flex min-h-svh max-w-md flex-col items-center justify-center px-6 text-center">
      <img src="/icon.svg" alt="" aria-hidden="true" class="mb-6 h-12 w-12" />

      <template v-if="status === 'closing'">
        <div
          class="h-9 w-9 animate-spin rounded-full border-2 border-[var(--line,#2a2d36)] border-t-[var(--accent,#1688ed)]"
          role="status"
          aria-label="Menutup jendela"
        />
        <h1 class="mt-5 text-lg font-semibold">Berhasil — menutup jendela…</h1>
        <p class="mt-1.5 text-xs leading-relaxed text-[var(--muted,#8992a2)]">
          Jendela ini akan tertutup otomatis. Anda bisa melanjutkan di halaman semula.
        </p>
      </template>

      <template v-else-if="status === 'working'">
        <div
          class="h-9 w-9 animate-spin rounded-full border-2 border-[var(--line,#2a2d36)] border-t-[var(--accent,#1688ed)]"
          role="status"
          aria-label="Memproses login"
        />
        <h1 class="mt-5 text-lg font-semibold">Menyelesaikan login…</h1>
        <p class="mt-1.5 text-xs leading-relaxed text-[var(--muted,#8992a2)]">
          Mohon tunggu sebentar, kami sedang memverifikasi akun Google Anda.
        </p>
      </template>

      <template v-else>
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-rose-500/10 text-2xl">⚠️</div>
        <h1 class="mt-4 text-lg font-semibold">Login Google Gagal</h1>
        <p class="mt-1.5 text-xs leading-relaxed text-[var(--muted,#8992a2)]">{{ errorMessage }}</p>
        <button
          type="button"
          class="mt-6 rounded-lg bg-[var(--accent,#1688ed)] px-5 py-2.5 text-sm font-semibold text-white transition-opacity hover:opacity-90"
          @click="backToLogin"
        >
          Kembali ke Halaman Masuk
        </button>
      </template>
    </div>
  </div>
</template>
