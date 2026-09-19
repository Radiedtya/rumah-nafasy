<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  VideoCameraIcon,
  MapPinIcon,
  ArrowRightIcon,
  BanknotesIcon,
  AcademicCapIcon,
  UserIcon,
  HeartIcon,
  HomeIcon,
  PencilSquareIcon,
} from '@heroicons/vue/24/outline'
import { useBookingStore, formatRupiah } from '../../../stores/booking'
import BaseButton from '../../../components/ui/BaseButton.vue'

const router = useRouter()
const store = useBookingStore()

// ── Kategori klien (harga = acuan budget, bukan tagihan aplikasi) ───────────
const CATEGORY_ICONS: Record<string, any> = {
  Siswa: AcademicCapIcon,
  Mahasiswa: AcademicCapIcon,
  Umum: UserIcon,
  Pasangan: HeartIcon,
  Keluarga: HomeIcon,
}

function categoryIcon(name: string) {
  return CATEGORY_ICONS[name] ?? UserIcon
}

const effectiveRate = computed(() => {
  if (store.requestedCategory) {
    return Number(store.requestedCategory.base_price)
  }
  return store.infoRate
})

const rateLabel = computed(() => {
  if (effectiveRate.value) return `${formatRupiah(effectiveRate.value)} / 60 menit`
  return 'Disepakati dengan psikolog'
})

// ── Durasi: 3 preset atau permintaan menit khusus ────────────────────────────
const presets = [
  { minutes: 30 as const, label: '30 Menit', desc: 'S singkat' },
  { minutes: 60 as const, label: '60 Menit', desc: 'Standar' },
  { minutes: 90 as const, label: '90 Menit', desc: 'Mendalam' },
]

const customMode = ref(false)
const customMinutesInput = ref<number | ''>('')
const customError = ref('')

function choosePreset(m: 30 | 60 | 90) {
  customMode.value = false
  customError.value = ''
  store.durationMinutes = m
}

function applyCustom() {
  const v = Number(customMinutesInput.value)
  if (!v || Number.isNaN(v)) {
    customError.value = 'Isi jumlah menit terlebih dahulu'
    return
  }
  if (v < 15 || v > 240) {
    customError.value = 'Durasi khusus antara 15–240 menit'
    return
  }
  customError.value = ''
  store.durationMinutes = v
}

const canProceed = computed(() => !!store.consultationType)

function proceed() {
  if (!canProceed.value) return
  if (customMode.value && customMinutesInput.value !== '' && !customError.value) {
    applyCustom()
  }
  router.push(`/dashboard/booking/${store.psikolog?.slug}/jadwal`)
}
</script>

<template>
  <div class="space-y-6">
    <!-- ═══ Banner "Konsultasi sekarang, bayar nanti" — SELALU PALING ATAS ═══ -->
    <section class="flex items-start gap-2.5 rounded-xl border border-emerald-500/30 bg-emerald-500/8 p-4">
      <BanknotesIcon class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
      <p class="text-xs leading-relaxed text-emerald-800 dark:text-emerald-300">
        <strong>Konsultasi sekarang, bayar nanti.</strong>
        Tidak ada pembayaran di aplikasi — pengajuan Anda akan ditinjau psikolog, dan pembayaran
        dilakukan langsung setelah sesi selesai.
      </p>
    </section>

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
          @click="store.consultationType = 'video'"
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
          @click="store.consultationType = 'offline'"
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

    <!-- 2. Kategori klien — harga per kategori sebagai acuan -->
    <section>
      <h2 class="text-sm font-semibold text-[var(--text)]">2. Kategori Klien</h2>
      <p class="mt-0.5 text-xs text-[var(--muted)]">
        Harga di bawah adalah <strong class="text-[var(--text)]">acuan biaya</strong> — siapkan budget
        sesuai kategori Anda. Nominal akhir tetap disepakati dengan psikolog saat sesi selesai.
      </p>
      <div class="mt-3 grid gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
        <button
          v-for="cat in store.categories"
          :key="cat.id"
          type="button"
          class="flex items-center gap-3 rounded-xl border p-4 text-left transition-colors"
          :class="
            store.requestedCategory?.id === cat.id
              ? 'border-[var(--accent)] bg-[var(--accent)]/8 ring-1 ring-[var(--accent)]/40'
              : 'border-[var(--line)] hover:bg-[var(--muted)]/6'
          "
          @click="store.requestedCategory = cat"
        >
          <component
            :is="categoryIcon(cat.name)"
            class="h-5 w-5 shrink-0 text-[var(--accent)]"
          />
          <div class="min-w-0">
            <p class="text-sm font-semibold text-[var(--text)]">{{ cat.name }}</p>
            <p class="text-xs tabular-nums text-[var(--muted)]">
              ~ {{ formatRupiah(cat.base_price) }} / 60 menit
            </p>
          </div>
        </button>
      </div>
    </section>

    <!-- 3. Durasi: preset atau permintaan khusus -->
    <section>
      <h2 class="text-sm font-semibold text-[var(--text)]">3. Durasi Sesi</h2>
      <p class="mt-0.5 text-xs text-[var(--muted)]">
        Durasi memengaruhi biaya — makin lama makin besar. Harga bisa bertambah atau berkurang
        sesuai jalannya sesi, dibayar saat selesai.
      </p>

      <div class="mt-3 grid gap-2.5 sm:grid-cols-3">
        <button
          v-for="p in presets"
          :key="p.minutes"
          type="button"
          class="rounded-xl border p-4 text-left transition-colors"
          :class="
            !customMode && store.durationMinutes === p.minutes
              ? 'border-[var(--accent)] bg-[var(--accent)]/8 ring-1 ring-[var(--accent)]/40'
              : 'border-[var(--line)] hover:bg-[var(--muted)]/6'
          "
          @click="choosePreset(p.minutes)"
        >
          <p class="text-sm font-semibold text-[var(--text)]">{{ p.label }}</p>
          <p class="mt-0.5 text-[11px] text-[var(--muted)]">{{ p.desc }}</p>
        </button>
      </div>

      <!-- Durasi khusus -->
      <div
        class="mt-2.5 rounded-xl border p-4 transition-colors"
        :class="customMode ? 'border-[var(--accent)] bg-[var(--accent)]/8' : 'border-[var(--line)]'"
      >
        <button
          type="button"
          class="flex w-full items-center gap-2.5 text-left"
          @click="customMode = !customMode"
        >
          <PencilSquareIcon class="h-4.5 w-4.5 shrink-0 text-[var(--accent)]" />
          <span class="text-sm font-medium text-[var(--text)]">
            Minta durasi khusus
          </span>
          <span
            v-if="customMode && store.durationMinutes && ![30, 60, 90].includes(store.durationMinutes)"
            class="ml-auto rounded bg-[var(--accent)]/10 px-2 py-0.5 text-[10px] font-semibold text-[var(--accent)]"
          >
            {{ store.durationMinutes }} menit
          </span>
        </button>

        <div v-if="customMode" class="mt-3 flex items-start gap-2">
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <input
                v-model.number="customMinutesInput"
                type="number"
                min="15"
                max="240"
                step="5"
                placeholder="Mis. 45"
                class="field-input w-28"
                @keyup.enter="applyCustom"
              />
              <span class="text-xs text-[var(--muted)]">menit (15–240)</span>
            </div>
            <p v-if="customError" class="mt-1.5 text-[11px] text-rose-600 dark:text-rose-400">
              {{ customError }}
            </p>
          </div>
          <BaseButton size="sm" variant="secondary" @click="applyCustom">
            Terapkan
          </BaseButton>
        </div>
      </div>
    </section>

    <!-- 4. Catatan opsional -->
    <section>
      <h2 class="text-sm font-semibold text-[var(--text)]">
        Catatan untuk Psikolog <span class="font-normal text-[var(--muted)]">(opsional)</span>
      </h2>
      <textarea
        v-model="store.note"
        rows="3"
        maxlength="500"
        placeholder="Contoh: kondisi atau keluhan yang ingin didiskusikan…"
        class="field-input mt-3 resize-none"
      />
    </section>

    <!-- Footer: tarif acuan + aksi -->
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4">
      <div>
        <p class="text-[11px] text-[var(--muted)]">Acuan Biaya</p>
        <p class="text-sm font-semibold tabular-nums text-[var(--text)]">{{ rateLabel }}</p>
        <p class="mt-0.5 text-[10px] text-[var(--muted)]">
          Dibayar langsung ke psikolog setelah sesi — bukan lewat aplikasi.
        </p>
      </div>
      <BaseButton size="md" :disabled="!canProceed" @click="proceed">
        Lanjut ke Jadwal
        <ArrowRightIcon class="h-3.5 w-3.5" />
      </BaseButton>
    </div>
  </div>
</template>
