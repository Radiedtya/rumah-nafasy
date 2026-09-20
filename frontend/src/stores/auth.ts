import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { apiFetch } from '../lib/api'

export interface UserProfile {
  id: number
  name: string
  email: string
  phone: string
  avatar?: string | null
  roles?: string[]
  /** Metode login tersedia (untuk pengaturan keamanan akun). */
  has_google?: boolean
  has_password?: boolean
  psikolog_profile?: {
    id: number
    slug: string
    bio: string
    specialization?: string
    experience_years: number
    license_no: string
    education: string
    workplace: string
    status: string
    is_available: boolean
    rating_avg: number
    total_reviews: number
    total_consultations: number
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(
    typeof window !== 'undefined' ? localStorage.getItem('token') : null
  )

  function getInitialUser(): UserProfile | null {
    if (typeof window === 'undefined') return null
    try {
      const saved = localStorage.getItem('user')
      return saved ? JSON.parse(saved) : null
    } catch {
      return null
    }
  }

  const user = ref<UserProfile | null>(getInitialUser())

  const isLoading = ref(false)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isPsikolog = computed(() => user.value?.roles?.includes('psikolog') ?? false)
  const isPasien = computed(() => user.value?.roles?.includes('pasien') ?? (!isPsikolog.value && !!user.value))
  const isAdmin = computed(() => user.value?.roles?.includes('admin') ?? false)

  function setSession(newToken: string, newUser: UserProfile) {
    token.value = newToken
    user.value = newUser
    if (typeof window !== 'undefined') {
      localStorage.setItem('token', newToken)
      localStorage.setItem('user', JSON.stringify(newUser))
    }
  }

  function clearSession() {
    token.value = null
    user.value = null
    if (typeof window !== 'undefined') {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }

  async function login(email: string, password: string, deviceName = 'browser-client') {
    isLoading.value = true
    try {
      const res = await apiFetch<{ user: UserProfile; token: string }>('auth/login', {
        method: 'POST',
        body: JSON.stringify({ email, password, device_name: deviceName }),
      })
      setSession(res.data.token, res.data.user)
      return res.data
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Registrasi metode email — server TIDAK mengembalikan token dan TIDAK
   * membuat akun dulu. Data disimpan sebagai pendaftaran pending; email
   * baru masuk database (tabel users) SETELAH OTP terverifikasi.
   * `verify_handle` = pengenal sesi OTP untuk halaman verifikasi.
   */
  async function register(data: {
    name: string
    email: string
    password: string
    password_confirmation: string
    phone?: string
    role?: 'pasien' | 'psikolog'
    turnstile_token?: string
  }) {
    isLoading.value = true
    try {
      const res = await apiFetch<{ email: string; verify_handle: string; requires_verification: boolean }>('auth/register', {
        method: 'POST',
        body: JSON.stringify(data),
      })
      // Sengaja TIDAK setSession — akun baru tercipta setelah OTP benar.
      return res.data
    } finally {
      isLoading.value = false
    }
  }

  /** Verifikasi OTP email → akun dibuat terverifikasi + token (masuk sesi). */
  async function verifyEmail(email: string, code: string, verifyHandle?: string) {
    const res = await apiFetch<{ user: UserProfile; token: string; verified: boolean }>('auth/email/verify', {
      method: 'POST',
      body: JSON.stringify({ email, code, ...(verifyHandle ? { verify_handle: verifyHandle } : {}) }),
    })
    setSession(res.data.token, res.data.user)
    return res.data
  }

  /** Kirim ulang OTP. Mengembalikan sisa cooldown (detik) bila 429. */
  async function resendEmailOtp(email: string, verifyHandle?: string) {
    const res = await apiFetch<{ resent: boolean; retry_after: number | null }>('auth/email/resend', {
      method: 'POST',
      body: JSON.stringify({ email, ...(verifyHandle ? { verify_handle: verifyHandle } : {}) }),
    })
    return res.data
  }

  /** Status verifikasi (publik, untuk sinkron countdown). */
  async function emailStatus(email: string, verifyHandle?: string) {
    const params = new URLSearchParams({ email })
    if (verifyHandle) params.set('verify_handle', verifyHandle)
    const res = await apiFetch<{ registered: boolean; verified: boolean; retry_after: number | null }>(
      `auth/email/status?${params.toString()}`,
      { method: 'GET' },
    )
    return res.data
  }

  async function logout() {
    try {
      if (token.value) {
        await apiFetch('auth/logout', { method: 'POST' }).catch(() => {})
      }
    } finally {
      clearSession()
    }
  }

  async function fetchMe() {
    if (!token.value) return null
    try {
      const res = await apiFetch<UserProfile>('auth/me')
      user.value = res.data
      if (typeof window !== 'undefined') {
        localStorage.setItem('user', JSON.stringify(res.data))
      }
      return res.data
    } catch (e: any) {
      if (e.status === 401) {
        clearSession()
      }
      return null
    }
  }

  async function updateProfile(data: { name?: string; phone?: string; avatar?: File }) {
    if (data.avatar) {
      // Step 1: upload avatar dulu ke dedicated endpoint
      await uploadAvatar(data.avatar)
      // Step 2: update name/phone jika ada
      if (data.name !== undefined || data.phone !== undefined) {
        const res = await apiFetch<UserProfile>('auth/profile', {
          method: 'PUT',
          body: JSON.stringify({ name: data.name, phone: data.phone }),
        })
        user.value = res.data
        if (typeof window !== 'undefined') {
          localStorage.setItem('user', JSON.stringify(res.data))
        }
      }
      return user.value!
    }

    // Tanpa file — JSON saja
    const res = await apiFetch<UserProfile>('auth/profile', {
      method: 'PUT',
      body: JSON.stringify({ name: data.name, phone: data.phone }),
    })
    user.value = res.data
    if (typeof window !== 'undefined') {
      localStorage.setItem('user', JSON.stringify(res.data))
    }
    return res.data
  }

  async function uploadAvatar(file: File) {
    const form = new FormData()
    form.append('avatar', file)
    // Dedicated POST endpoint — tidak perlu _method spoofing
    const res = await apiFetch<UserProfile>('auth/profile/avatar', {
      method: 'POST',
      body: form,
    })
    user.value = res.data
    if (typeof window !== 'undefined') {
      localStorage.setItem('user', JSON.stringify(res.data))
    }
    return res.data
  }

  async function deleteAvatar() {
    const res = await apiFetch<UserProfile>('auth/profile/avatar', { method: 'DELETE' })
    user.value = res.data
    if (typeof window !== 'undefined') {
      localStorage.setItem('user', JSON.stringify(res.data))
    }
    return res.data
  }

  /**
   * Ubah / atur password (halaman Profil → Metode Masuk).
   * - User punya password → wajib current_password.
   * - Akun Google murni → wajib otp_token + otp_code dari sendSetPasswordOtp().
   */
  async function updatePassword(data: {
    current_password?: string
    otp_token?: string
    otp_code?: string
    password: string
    password_confirmation: string
  }) {
    const res = await apiFetch<UserProfile>('auth/password', {
      method: 'PUT',
      body: JSON.stringify(data),
    })
    user.value = res.data
    if (typeof window !== 'undefined') {
      localStorage.setItem('user', JSON.stringify(res.data))
    }
    return res.data
  }

  /** Kirim OTP email untuk proses set-password (akun Google murni). */
  async function sendSetPasswordOtp() {
    const res = await apiFetch<{ sent: boolean; email: string; otp_token: string; retry_after: number | null }>(
      'auth/password/set-otp',
      { method: 'POST' },
    )
    return res.data
  }

  return {
    token,
    user,
    isLoading,
    isAuthenticated,
    isPsikolog,
    isPasien,
    isAdmin,
    login,
    register,
    verifyEmail,
    resendEmailOtp,
    emailStatus,
    logout,
    fetchMe,
    updateProfile,
    uploadAvatar,
    deleteAvatar,
    updatePassword,
    sendSetPasswordOtp,
    setSession,
    clearSession,
  }
})
