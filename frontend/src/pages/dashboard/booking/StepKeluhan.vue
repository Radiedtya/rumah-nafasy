<script setup lang="ts">
import { useRouter } from 'vue-router'
import {
  ArrowLeftIcon,
  ArrowRightIcon,
  DocumentTextIcon,
  LightBulbIcon,
  LockClosedIcon,
} from '@heroicons/vue/24/outline'
import { useBookingStore } from '../../../stores/booking'
import BaseButton from '../../../components/ui/BaseButton.vue'
import MarkdownEditor from '../../../components/dashboard/booking/MarkdownEditor.vue'

const router = useRouter()
const store = useBookingStore()

function back() {
  router.push(`/dashboard/booking/${store.psikolog?.slug}`)
}

function proceed() {
  router.push(`/dashboard/booking/${store.psikolog?.slug}/jadwal`)
}
</script>

<template>
  <div class="space-y-6">
    <section>
      <h2 class="text-sm font-semibold text-[var(--text)]">Keluhan &amp; Catatan untuk Psikolog</h2>
      <p class="mt-0.5 text-xs leading-relaxed text-[var(--muted)]">
        Ceritakan kondisi atau keluhan yang ingin didiskusikan — psikolog membacanya
        <strong class="text-[var(--text)]">sebelum sesi dimulai</strong> agar bisa menyiapkan pendekatan
        yang tepat. Boleh juga dilewati.
      </p>
    </section>

    <MarkdownEditor
      v-model="store.complaint"
      placeholder="Contoh:&#10;&#10;## Keluhan utama&#10;Sulit tidur dan cemas berlebihan sejak 2 bulan terakhir…&#10;&#10;- Muncul saat jam kerja&#10;- Sering disertai jantung berdebar"
      :rows="12"
    />

    <!-- Tips menulis keluhan yang efektif -->
    <section class="rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4">
      <div class="flex items-start gap-2.5">
        <LightBulbIcon class="mt-0.5 h-4.5 w-4.5 shrink-0 text-amber-500" />
        <div class="text-xs leading-relaxed text-[var(--muted)]">
          <p class="font-semibold text-[var(--text)]">Tips menulis keluhan yang efektif</p>
          <ul class="mt-1.5 list-inside list-disc space-y-1">
            <li>Jelaskan <strong class="text-[var(--text)]">sejak kapan</strong> keluhan muncul dan seberapa sering</li>
            <li>Catat <strong class="text-[var(--text)]">pemicu</strong> yang Anda sadari (pekerjaan, keluarga, dll)</li>
            <li>Sebutkan apa yang sudah Anda coba untuk mengatasinya</li>
            <li>Tidak perlu terlalu detail — cukup poin pentingnya, sisanya didiskusikan saat sesi</li>
          </ul>
        </div>
      </div>
    </section>

    <!-- Catatan privasi -->
    <div class="flex items-start gap-2.5 rounded-xl bg-[var(--muted)]/6 p-3.5">
      <LockClosedIcon class="mt-0.5 h-4 w-4 shrink-0 text-[var(--accent)]" />
      <p class="text-[11px] leading-relaxed text-[var(--muted)]">
        Isi keluhan <strong class="text-[var(--text)]">hanya terlihat oleh psikolog yang Anda pilih</strong>
        (dan admin untuk kebutuhan operasional), tidak ditampilkan publik.
      </p>
    </div>

    <div class="flex items-center justify-between gap-4 rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4">
      <BaseButton variant="secondary" size="md" @click="back">
        <ArrowLeftIcon class="h-3.5 w-3.5" />
        Kembali
      </BaseButton>
      <div class="flex items-center gap-3">
        <BaseButton variant="ghost" size="md" @click="proceed">
          Lewati
        </BaseButton>
        <BaseButton size="md" @click="proceed">
          <DocumentTextIcon class="h-3.5 w-3.5" />
          Lanjut ke Jadwal
          <ArrowRightIcon class="h-3.5 w-3.5" />
        </BaseButton>
      </div>
    </div>
  </div>
</template>
