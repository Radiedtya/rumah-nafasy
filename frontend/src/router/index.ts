import type { RouteRecordRaw } from 'vue-router'
import PublicLayout from '../layouts/PublicLayout.vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'

/**
 * ── Guard autentikasi produksi ──────────────────────────────────────────────
 * Dipasang sebagai `beforeEnter` (bukan router.beforeEach) agar terdaftar
 * sejak routes dibuat — aman untuk navigasi awal di ViteSSG. Sengaja tidak
 * memakai Pinia: guard berjalan sebelum app.use(pinia) pada navigasi pertama.
 * Token di localStorage adalah gate; otorisasi sesungguhnya tetap
 * diverifikasi server di setiap request API.
 */

function readSession(): { token: string; roles: string[] } | null {
  if (typeof window === 'undefined') return null
  const token = window.localStorage.getItem('token')
  if (!token) return null
  try {
    const user = JSON.parse(window.localStorage.getItem('user') ?? 'null')
    return { token, roles: Array.isArray(user?.roles) ? user.roles : [] }
  } catch {
    return { token, roles: [] }
  }
}

/** Halaman privat — wajib login, else → /login?redirect=... */
function requireAuth(to: { fullPath: string }) {
  return readSession() ? true : { path: '/login', query: { redirect: to.fullPath } }
}

/** Halaman berbasis role — role salah → kembali ke dashboard miliknya. */
function requireRole(role: string) {
  return (to: { fullPath: string }) => {
    const session = readSession()
    if (!session) return { path: '/login', query: { redirect: to.fullPath } }
    if (!session.roles.includes(role)) return { path: '/dashboard' }
    return true
  }
}

/** Login/register — kalau sudah punya sesi, tidak ada alasan berada di sini. */
function guestOnly() {
  return readSession() ? { path: '/dashboard' } : true
}

export const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: PublicLayout,
    children: [{ path: '', component: () => import('../pages/Home.vue') }],
  },
  {
    path: '/login',
    component: () => import('../pages/Login.vue'),
    meta: { ssg: false },
    beforeEnter: [guestOnly],
  },
  {
    path: '/register',
    component: () => import('../pages/Register.vue'),
    meta: { ssg: false },
    beforeEnter: [guestOnly],
  },
  {
    path: '/dashboard',
    component: DashboardLayout,
    meta: { ssg: false },
    beforeEnter: [requireAuth],
    children: [
      { path: '', component: () => import('../pages/dashboard/Overview.vue') },
      { path: 'sesi', component: () => import('../pages/dashboard/Sesi.vue') },
      { path: 'psikolog', component: () => import('../pages/dashboard/Psikolog.vue') },
      {
        path: 'booking/:slug',
        component: () => import('../pages/dashboard/booking/BookingLayout.vue'),
        meta: { ssg: false },
        children: [
          { path: '', component: () => import('../pages/dashboard/booking/StepPaket.vue'), meta: { ssg: false } },
          { path: 'jadwal', component: () => import('../pages/dashboard/booking/StepJadwal.vue'), meta: { ssg: false } },
          { path: 'selesai', component: () => import('../pages/dashboard/booking/StepSelesai.vue'), meta: { ssg: false } },
        ],
      },
      { path: 'profil', component: () => import('../pages/dashboard/Profil.vue') },
      { path: 'jadwal', component: () => import('../pages/dashboard/PsikologJadwal.vue'), beforeEnter: [requireRole('psikolog')] },
      { path: 'konsultasi', component: () => import('../pages/dashboard/PsikologKonsultasi.vue'), beforeEnter: [requireRole('psikolog')] },
      // ── Admin only ──────────────────────────────────────────────────────────
      { path: 'pengguna', component: () => import('../pages/dashboard/admin/Pengguna.vue'), beforeEnter: [requireRole('admin')] },
      { path: 'semua-jadwal', component: () => import('../pages/dashboard/admin/SemuaJadwal.vue'), beforeEnter: [requireRole('admin')] },
      { path: 'semua-konsultasi', component: () => import('../pages/dashboard/admin/SemuaKonsultasi.vue'), beforeEnter: [requireRole('admin')] },
    ],
  },
]
