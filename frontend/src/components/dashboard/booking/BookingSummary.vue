<script setup lang="ts">
import { computed } from 'vue'
import { useBookingStore, formatRupiah } from '../../../stores/booking'
import { BanknotesIcon } from '@heroicons/vue/24/outline'
import BaseAvatar from '../../ui/BaseAvatar.vue'

const store = useBookingStore()

const psikolog = computed(() => store.psikolog)
const typeLabel = computed(() => (store.consultationType === 'offline' ? 'Offline (Tatap Muka)' : 'Video Call'))
</script>

<template>
  <aside class="space-y-4 lg:sticky lg:top-4">
    <!-- Psikolog -->
    <div class="rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4">
      <p class="text-[11px] font-semibold uppercase tracking-wide text-[var(--muted)]">Psikolog Pilihan</p>
      <div v-if="psikolog" class="mt-3 flex items-center gap-3">
        <BaseAvatar :name="psikolog.name" :src="psikolog.avatar" size="md" />
        <div class="min-w-0">
          <p class="truncate text-sm font-semibold text-[var(--text)]">{{ psikolog.name }}</p>
          <p class="truncate text-xs text-[var(--accent)]">{{ psikolog.specialization || 'Psikolog Klinis' }}</p>
        </div>
      </div>
      <div v-else class="mt-3 space-y-2">
        <div class="h-9 w-9 animate-pulse rounded-full bg-[var(--muted)]/15" />
        <div class="h-3 w-2/3 animate-pulse rounded bg-[var(--muted)]/15" />
      </div>
      <p v-if="psikolog?.workplace" class="mt-2.5 truncate text-[11px] text-[var(--muted)]">
        {{ psikolog.workplace }}
      </p>
    </div>

    <!-- Rincian -->
    <div class="rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4">
      <p class="text-[11px] font-semibold uppercase tracking-wide text-[var(--muted)]">Rincian</p>
      <dl class="mt-3 space-y-2.5 text-xs">
        <div class="flex items-center justify-between gap-3">
          <dt class="text-[var(--muted)]">Jenis</dt>
          <dd class="font-medium text-[var(--text)]">{{ typeLabel }}</dd>
        </div>
        <div class="flex items-center justify-between gap-3">
          <dt class="text-[var(--muted)]">Durasi</dt>
          <dd class="font-medium text-[var(--text)]">{{ store.durationMinutes }} menit</dd>
        </div>
        <div class="flex items-center justify-between gap-3">
          <dt class="text-[var(--muted)]">Jadwal</dt>
          <dd class="font-medium text-[var(--text)]">
            <template v-if="store.bookingDate && store.selectedSlot">
              {{ store.bookingDate }} · {{ store.selectedSlot.start_time }}
            </template>
            <template v-else>—</template>
          </dd>
        </div>
        <div v-if="store.infoRate" class="flex items-center justify-between gap-3 border-t border-[var(--line)] pt-2.5">
          <dt class="text-[var(--muted)]">Tarif (info)</dt>
          <dd class="tabular-nums text-[var(--text)]">~ {{ formatRupiah(store.infoRate) }} / sesi</dd>
        </div>
      </dl>

      <!-- Catatan pembayaran P2P -->
      <div class="mt-3 flex items-start gap-2 rounded-lg bg-[var(--muted)]/6 p-2.5">
        <BanknotesIcon class="mt-0.5 h-4 w-4 shrink-0 text-[var(--accent)]" />
        <p class="text-[11px] leading-relaxed text-[var(--muted)]">
          <strong class="text-[var(--text)]">Tanpa pembayaran di aplikasi</strong> — pembayaran dilakukan
          langsung ke psikolog setelah sesi selesai. Nominal disepakati bersama.
        </p>
      </div>
    </div>
  </aside>
</template>
