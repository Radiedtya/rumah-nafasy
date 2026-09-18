import type { RouteRecordRaw } from 'vue-router'
import PublicLayout from '../layouts/PublicLayout.vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'

export const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: PublicLayout,
    children: [{ path: '', component: () => import('../pages/Home.vue') }],
  },
  { path: '/login', component: () => import('../pages/Login.vue'), meta: { ssg: false } },
  { path: '/register', component: () => import('../pages/Register.vue'), meta: { ssg: false } },
  {
    path: '/dashboard',
    component: DashboardLayout,
    meta: { ssg: false },
    children: [
      { path: '', component: () => import('../pages/dashboard/Overview.vue') },
      { path: 'sesi', component: () => import('../pages/dashboard/Sesi.vue') },
      { path: 'psikolog', component: () => import('../pages/dashboard/Psikolog.vue') },
      { path: 'profil', component: () => import('../pages/dashboard/Profil.vue') },
      { path: 'jadwal', component: () => import('../pages/dashboard/PsikologJadwal.vue') },
      { path: 'konsultasi', component: () => import('../pages/dashboard/PsikologKonsultasi.vue') },
      // ── Admin only ──────────────────────────────────────────────────────────
      { path: 'pengguna', component: () => import('../pages/dashboard/admin/Pengguna.vue') },
      { path: 'semua-jadwal', component: () => import('../pages/dashboard/admin/SemuaJadwal.vue') },
      { path: 'semua-konsultasi', component: () => import('../pages/dashboard/admin/SemuaKonsultasi.vue') },
    ],
  },
]
