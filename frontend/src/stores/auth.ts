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
      const res = await apiFetch<{ user: UserProfile; token: string }>('auth/register', {
        method: 'POST',
        body: JSON.stringify(data),
      })
      setSession(res.data.token, res.data.user)
      return res.data
    } finally {
      isLoading.value = false
    }
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
    logout,
    fetchMe,
    updateProfile,
    uploadAvatar,
    deleteAvatar,
    setSession,
    clearSession,
  }
})
