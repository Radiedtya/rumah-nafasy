<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  VideoCameraIcon,
  MapPinIcon,
  ArrowRightIcon,
  BanknotesIcon,
} from '@heroicons/vue/24/outline'
import { useBookingStore, formatRupiah, type ConsultationType } from '../../../stores/booking'
import BaseButton from '../../../components/ui/BaseButton.vue'

const router = useRouter()
const store = useBookingStore()

const canProceed = computed(() => !!store.consultationType && !!store.durationMinutes)

const durations = [
  { minutes: 30 as const, label: '30 Menit' },
  { minutes: 60 as const, label: '60 Menit' },
  { minutes: 90 as const, label: '90 Menit' },
]

function chooseType(type: ConsultationType) {
  store.consultationType = type
}
</script>

<template>
  <div class="space-y-6">
    <!-- 1. Jenis konsultasi -->
    <section>
      <h2 class="text-sm font-semibold text-[var(--text)]">1. Pilih Jenis Konsultasi</h2>
      <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
        <button
          type="button"
          class="flex items-center gap-3 rounded-xl border p-4 text-left transition-colors"
          :class="
            store.consultationType === 'video'
              ? 'border-[var(--accent)] bg-[var(--accent)]/8 ring-1 ring-[var(--accent)]/40'
              : 'border-[var(--line)] hover:bg-[var(--muted)]/6'
          "
          @click="chooseType('video')"
        >
          <VideoCameraIcon class="h-5 w-5 shrink-0 text-[var(--accent)]" />
          <div>
            <p class="text-sm font-semibold text-[var(--text)]">Video Call</p>
            <p class="text-xs text-[var(--muted)]">Sesi via ruang video online</p>
          </div>
        </button>
        <button
          type="button"
          class="flex items-center gap-3 rounded-xl border p-4 text-left transition-colors"
          :class="
            store.consultationType === 'offline'
              ? 'border-[var(--accent)] bg-[var(--accent)]/8 ring-1 ring-[var(--accent)]/40'
              : 'border-[var(--line)] hover:bg-[var(--muted)]/6'
          "
          @click="chooseType('offline')"
        >
          <MapPinIcon class="h-5 w-5 shrink-0 text-[var(--accent)]" />
          <div>
            <p class="text-sm font-semibold text-[var(--text)]">Offline (Tatap Muka)</p>
            <p class="text-xs text-[var(--muted)]">Sesi langsung di lokasi praktik</p>
          </div>
        </button>
      </div>
      <p v-if="store.consultationType === 'offline' && store.psikolog?.workplace" class="mt-2 text-[11px] text-[var(--muted)]">
        Lokasi praktik: <span class="font-medium text-[var(--text)]">{{ store.psikolog.workplace }}</span>
      </p>
    </section>

    <!-- 2. Durasi -->
    <section>
      <h2 class="text-sm font-semibold text-[var(--text)]">2. Pilih Durasi Sesi</h2>
      <div class="mt-3 grid gap-2.5 sm:grid-cols-3">
        <button
          v-for="d in durations"
          :key="d.minutes"
          type="button"
          class="rounded-xl border p-4 text-left transition-colors"
          :class="
            store.durationMinutes === d.minutes
              ? 'border-[var(--accent)] bg-[var(--accent)]/8 ring-1 ring-[var(--accent)]/40'
              : 'border-[var(--line)] hover:bg-[var(--muted)]/6'
          "
          @click="store.durationMinutes = d.minutes"
        >
          <p class="text-sm font-semibold text-[var(--text)]">{{ d.label }}</p>
        </button>
      </div>
    </section>

    <!-- 3. Catatan opsional -->
    <section>
      <h2 class="text-sm font-semibold text-[var(--text)]">3. Catatan untuk Psikolog <span class="font-normal text-[var(--muted)]">(opsional)</span></h2>
      <textarea
        v-model="store.note"
        rows="3"
        maxlength="500"
        placeholder="Contoh: kondisi atau keluhan yang ingin didiskusikan…"
        class="field-input mt-3 resize-none"
      />
    </section>

    <!-- Info pembayaran -->
    <section class="flex items-start gap-2.5 rounded-xl border border-dashed border-[var(--line)] bg-[var(--muted)]/5 p-4">
      <BanknotesIcon class="mt-0.5 h-4.5 w-4.5 shrink-0 text-[var(--accent)]" />
      <p class="text-xs leading-relaxed text-[var(--muted)]">
        <strong class="text-[var(--text)]">Konsultasi sekarang, bayar nanti.</strong>
        Tidak ada pembayaran di aplikasi — pengajuan Anda akan ditinjau psikolog, dan pembayaran
        dilakukan langsung setelah sesi selesai
        <template v-if="store.infoRate"> (tarif acuan ~ {{ formatRupiah(store.infoRate) }} / sesi, dapat disepakati ulang)</template>.
      </p>
    </section>

    <!-- Footer aksi -->
    <div class="flex items-center justify-end rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4">
      <BaseButton size="md" :disabled="!canProceed" @click="router.push(`/dashboard/booking/${store.psikolog?.slug}/jadwal`)">
        Lanjut ke Jadwal
        <ArrowRightIcon class="h-3.5 w-3.5" />
      </BaseButton>
    </div>
  </div>
</template>
