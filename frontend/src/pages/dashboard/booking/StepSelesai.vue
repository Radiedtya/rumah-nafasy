<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import {
  CheckCircleIcon,
  CalendarDaysIcon,
  ClockIcon,
  VideoCameraIcon,
  ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline'
import { useBookingStore, formatRupiah } from '../../../stores/booking'
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
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/10">
        <CheckCircleIcon class="h-9 w-9 text-emerald-600 dark:text-emerald-400" />
      </div>
      <h2 class="mt-4 text-lg font-semibold tracking-tight text-[var(--text)]">
        Reservasi Berhasil Dikonfirmasi
      </h2>
      <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-[var(--muted)]">
        Sesi konsultasi Anda dengan
        <strong class="text-[var(--text)]">{{ store.psikolog?.name }}</strong>
        telah dijadwalkan.
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
              {{ booking?.start_time }} – {{ booking?.end_time }} WIB
            </p>
          </div>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-[var(--line)] px-4 py-3">
          <VideoCameraIcon class="h-4.5 w-4.5 shrink-0 text-[var(--accent)]" />
          <div>
            <p class="text-[10px] font-semibold uppercase tracking-wide text-[var(--muted)]">Total Dibayar</p>
            <p class="text-sm font-medium tabular-nums text-[var(--text)]">
              {{ formatRupiah(store.order?.calculated_price) }}
            </p>
          </div>
        </div>
      </div>

      <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
        <RouterLink to="/dashboard/sesi">
          <BaseButton variant="secondary" size="md">Lihat di Sesi Saya</BaseButton>
        </RouterLink>
        <a
          v-if="booking?.room_id"
          :href="`https://meet.jit.si/${booking.room_id}`"
          target="_blank"
          rel="noopener noreferrer"
        >
          <BaseButton size="md">
            Uji Ruang Video Call
            <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5" />
          </BaseButton>
        </a>
      </div>
    </section>
  </div>
</template>
