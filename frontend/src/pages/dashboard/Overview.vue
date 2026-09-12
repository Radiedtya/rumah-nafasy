<script setup lang="ts">
import { CalendarDaysIcon, ChatBubbleLeftRightIcon, ClockIcon, StarIcon } from '@heroicons/vue/24/outline'

const stats = [
  { label: 'Total Sesi', value: '12', change: '+2 bulan ini', icon: CalendarDaysIcon, color: 'bg-sky-100 text-sky-600' },
  { label: 'Sesi Mendatang', value: '2', change: 'Pekan ini', icon: ClockIcon, color: 'bg-amber-100 text-amber-600' },
  { label: 'Pesan Belum Dibaca', value: '3', change: 'Dari psikolog', icon: ChatBubbleLeftRightIcon, color: 'bg-rose-100 text-rose-600' },
  { label: 'Rating Rata-rata', value: '4.9', change: 'Dari 10 ulasan', icon: StarIcon, color: 'bg-emerald-100 text-emerald-600' },
]

const upcomingSessions = [
  {
    psikolog: 'Dra. Sari Dewi, M.Psi.',
    topic: 'Manajemen Kecemasan',
    date: 'Sabtu, 12 Sep 2026',
    time: '10:00 WIB',
    duration: '60 menit',
    type: 'Video Call',
    avatarColor: 'linear-gradient(135deg, #f9a8a0, #fbd0c8)',
  },
  {
    psikolog: 'Rian Kusuma, M.Psi.',
    topic: 'Burnout & Work-life Balance',
    date: 'Senin, 14 Sep 2026',
    time: '19:00 WIB',
    duration: '45 menit',
    type: 'Chat Teks',
    avatarColor: 'linear-gradient(135deg, #d8b4fe, #e9d5ff)',
  },
]

const recentActivity = [
  { text: 'Sesi selesai dengan Dr. Bima Arya', time: '3 hari lalu', icon: '✅' },
  { text: 'Pembayaran berhasil — Rp 130.000', time: '3 hari lalu', icon: '💳' },
  { text: 'Booking sesi baru dengan Dra. Sari Dewi', time: '5 hari lalu', icon: '📅' },
  { text: 'Reminder sesi dikirim via WhatsApp', time: '8 hari lalu', icon: '🔔' },
]
</script>

<template>
  <div class="max-w-5xl mx-auto space-y-6">
    <!-- Greeting -->
    <div class="bg-gradient-to-br from-rose-50 to-orange-50 border border-rose-100 rounded-2xl p-6 flex items-center justify-between gap-4">
      <div>
        <p class="text-xs text-rose-500 font-semibold uppercase tracking-widest mb-1">Selamat datang kembali</p>
        <h2 class="font-display text-xl font-semibold text-neutral-900">Halo, Anisa 👋</h2>
        <p class="text-neutral-600 text-sm mt-1">Sesi berikutnya dalam <strong class="text-rose-500">2 hari</strong>. Tetap semangat!</p>
      </div>
      <RouterLink
        to="/dashboard/psikolog"
        class="shrink-0 inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-rose-500 text-white text-sm font-semibold hover:bg-rose-600 active:scale-95 transition-all"
      >
        + Booking Sesi
      </RouterLink>
    </div>

    <!-- Stats grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div
        v-for="stat in stats"
        :key="stat.label"
        class="bg-white rounded-2xl border border-neutral-200 p-5 flex flex-col gap-3"
      >
        <div class="flex items-start justify-between">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center" :class="stat.color">
            <component :is="stat.icon" class="w-5 h-5" aria-hidden="true" />
          </div>
        </div>
        <div>
          <p class="font-display text-2xl font-bold text-neutral-900">{{ stat.value }}</p>
          <p class="text-neutral-500 text-xs mt-0.5">{{ stat.label }}</p>
        </div>
        <p class="text-[10px] text-neutral-400">{{ stat.change }}</p>
      </div>
    </div>

    <!-- Dua kolom: Upcoming + Aktivitas -->
    <div class="grid md:grid-cols-2 gap-5">
      <!-- Sesi mendatang -->
      <div class="bg-white rounded-2xl border border-neutral-200 p-5">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-neutral-900 text-sm">Sesi Mendatang</h3>
          <RouterLink to="/dashboard/sesi" class="text-xs text-rose-500 font-semibold hover:opacity-70">Lihat semua</RouterLink>
        </div>
        <div class="flex flex-col gap-3">
          <div
            v-for="sesi in upcomingSessions"
            :key="sesi.psikolog"
            class="flex items-start gap-3 p-3 rounded-xl bg-neutral-50 border border-neutral-100"
          >
            <div class="w-10 h-10 rounded-full shrink-0" :style="{ background: sesi.avatarColor }" />
            <div class="min-w-0 flex-1">
              <p class="text-xs font-semibold text-neutral-900 truncate">{{ sesi.psikolog }}</p>
              <p class="text-[10px] text-neutral-500 mt-0.5">{{ sesi.topic }}</p>
              <div class="flex items-center gap-2 mt-2 flex-wrap">
                <span class="text-[10px] bg-sky-100 text-sky-600 px-2 py-0.5 rounded-full font-medium">{{ sesi.date }}</span>
                <span class="text-[10px] bg-amber-100 text-amber-600 px-2 py-0.5 rounded-full font-medium">{{ sesi.time }}</span>
                <span class="text-[10px] bg-neutral-100 text-neutral-600 px-2 py-0.5 rounded-full">{{ sesi.type }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Aktivitas terbaru -->
      <div class="bg-white rounded-2xl border border-neutral-200 p-5">
        <h3 class="font-semibold text-neutral-900 text-sm mb-4">Aktivitas Terbaru</h3>
        <ol class="flex flex-col gap-3" aria-label="Riwayat aktivitas">
          <li
            v-for="activity in recentActivity"
            :key="activity.text"
            class="flex items-start gap-3"
          >
            <span class="text-base shrink-0 mt-0.5" aria-hidden="true">{{ activity.icon }}</span>
            <div class="min-w-0">
              <p class="text-xs text-neutral-800 leading-snug">{{ activity.text }}</p>
              <p class="text-[10px] text-neutral-400 mt-0.5">{{ activity.time }}</p>
            </div>
          </li>
        </ol>
      </div>
    </div>
  </div>
</template>
