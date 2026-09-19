<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import {
  CalendarDaysIcon,
  ClockIcon,
  VideoCameraIcon,
  MapPinIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'
import { useBookingStore } from '../../../stores/booking'
import BaseButton from '../../../components/ui/BaseButton.vue'

const store = useBookingStore()

const booking = computed(() => store.confirmedBooking)

function formatDate(d: string | null | undefined) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}
</script>

<template>
  <div class="space-y-6">
    <section class="rounded-2xl border border-[var(--line)] bg-[var(--surface)] p-8 text-center">
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/10">
        <ArrowPathIcon class="h-9 w-9 text-amber-600 dark:text-amber-400" />
      </div>
      <h2 class="mt-4 text-lg font-semibold tracking-tight text-[var(--text)]">
        Pengajuan Terkirim — Menunggu Persetujuan
      </h2>
      <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-[var(--muted)]">
        Pengajuan konsultasi dengan
        <strong class="text-[var(--text)]">{{ store.psikolog?.name }}</strong>
        telah dikirim. Slot ditahan untuk Anda sampai psikolog menyetujui.
      </p>

      <div class="mx-auto mt-6 grid max-w-md gap-3 text-left">
        <div class="flex items-center gap-3 rounded-xl border border-[var(--line)] px-4 py-3">
          <CalendarDaysIcon class="h-4.5 w-4.5 shrink-0 text-[var(--accent)]" />
          <div>
            <p class="text-[10px] font-semibold uppercase tracking-wide text-[var(--muted)]">Tanggal</p>
            <p class="text-sm font-medium text-[var(--text)]">{{ formatDate(booking?.booking_date) }}</p>
          </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-[var(--line)] px-4 py-3">
          <ClockIcon class="h-4.5 w-4.5 shrink-0 text-[var(--accent)]" />
          <div>
            <p class="text-[10px] font-semibold uppercase tracking-wide text-[var(--muted)]">Waktu</p>
            <p class="text-sm font-medium tabular-nums text-[var(--text)]">
              {{ booking?.start_time }} – {{ booking?.end_time }} WIB · {{ booking?.duration_minutes ?? store.durationMinutes }} menit
            </p>
          </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-[var(--line)] px-4 py-3">
          <VideoCameraIcon v-if="booking?.consultation_type !== 'offline'" class="h-4.5 w-4.5 shrink-0 text-[var(--accent)]" />
          <MapPinIcon v-else class="h-4.5 w-4.5 shrink-0 text-[var(--accent)]" />
          <div>
            <p class="text-[10px] font-semibold uppercase tracking-wide text-[var(--muted)]">Jenis</p>
            <p class="text-sm font-medium text-[var(--text)]">
              {{ booking?.consultation_type === 'offline' ? 'Offline (Tatap Muka)' : 'Video Call' }}
            </p>
          </div>
        </div>
      </div>

      <div class="mx-auto mt-5 max-w-md rounded-xl bg-[var(--muted)]/6 p-3.5 text-left">
        <p class="text-[11px] leading-relaxed text-[var(--muted)]">
          💳 <strong class="text-[var(--text)]">Pembayaran</strong> dilakukan langsung ke psikolog
          <strong class="text-[var(--text)]">setelah sesi selesai</strong> — tidak melalui aplikasi.
        </p>
      </div>

      <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
        <RouterLink to="/dashboard/sesi">
          <BaseButton variant="secondary" size="md">Lihat di Sesi Saya</BaseButton>
        </RouterLink>
        <RouterLink to="/dashboard">
          <BaseButton size="md">Kembali ke Dashboard</BaseButton>
        </RouterLink>
      </div>
    </section>
  </div>
</template>
