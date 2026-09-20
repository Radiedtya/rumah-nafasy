<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { CalendarDate, DateFormatter, getLocalTimeZone, today } from '@internationalized/date'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

/**
 * Kalender booking custom — bukan input date HTML.
 *
 * Visual hierarchy:
 *  - tanggal terbuka     → normal, bisa diklik (hover accent)
 *  - tanggal penuh/tutup → redup jelas + tidak bisa diklik (tooltip menjelaskan)
 *  - tanggal lampau      → lebih pudar lagi, disabled
 *
 * Catatan teknis: state reaktif hanya menyimpan angka & string ISO — objek
 * CalendarDate dibuat lokal di fungsi, karena UnwrapRef Vue mem-flatten class
 * eksternal dan merusak tipenya.
 */

export interface CalendarDayAvailability {
  date: string // 'YYYY-MM-DD'
  available: boolean
  slots: number
  first_start: string | null
}

interface GridDay {
  iso: string
  day: number
  inMonth: boolean
}

const props = withDefaults(
  defineProps<{
    modelValue: string | null
    availability: Record<string, CalendarDayAvailability>
    /** Tanggal minimum yang bisa dipilih. Default: besok. */
    min?: string
  }>(),
  { min: undefined },
)

const emit = defineEmits<{
  (e: 'update:modelValue', v: string): void
  (e: 'change', v: string): void
  (e: 'monthChange', from: string): void
}>()

const df = new DateFormatter('id-ID', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
  year: 'numeric',
})
const tz = getLocalTimeZone()

function parseIso(iso: string): CalendarDate {
  const [y, m, d] = iso.split('-').map(Number)
  return new CalendarDate(y, m, d)
}

function isoOf(d: CalendarDate): string {
  return `${d.year}-${String(d.month).padStart(2, '0')}-${String(d.day).padStart(2, '0')}`
}

const todayValue: CalendarDate = today(tz)
const todayIso = isoOf(todayValue)

function getMin(): CalendarDate {
  return props.min ? parseIso(props.min) : todayValue.add({ days: 1 })
}

/* ── Bulan tampilan (angka murni, bukan objek) ─────────────────────────── */
const initial = props.modelValue ? parseIso(props.modelValue) : getMin()
const vy = ref(initial.year)
const vm = ref(initial.month)

/** Beri tahu parent bulan yang sedang tampil — dipanggil saat mount & saat pindah bulan. */
function emitVisibleMonth() {
  emit('monthChange', isoOf(new CalendarDate(vy.value, vm.value, 1)))
}
onMounted(emitVisibleMonth)

watch(
  () => props.modelValue,
  (v) => {
    if (v) {
      const d = parseIso(v)
      if (d.year !== vy.value || d.month !== vm.value) {
        vy.value = d.year
        vm.value = d.month
      }
    }
  },
)

/* ── Grid: 6 pekan × 7 kolom, Senin sebagai awal pekan ─────────────────── */
const grid = computed<GridDay[][]>(() => {
  const first = new CalendarDate(vy.value, vm.value, 1)
  const offsetToMonday = (first.toDate(tz).getDay() + 6) % 7
  const gridStart = first.subtract({ days: offsetToMonday })

  const weeks: GridDay[][] = []
  for (let w = 0; w < 6; w++) {
    const row: GridDay[] = []
    for (let i = 0; i < 7; i++) {
      const d = gridStart.add({ days: w * 7 + i })
      row.push({ iso: isoOf(d), day: d.day, inMonth: d.month === vm.value && d.year === vy.value })
    }
    weeks.push(row)
  }
  return weeks
})

const weekdayLabels = computed<string[]>(() => {
  const monday = new CalendarDate(2024, 1, 1) // 1 Jan 2024 = Senin
  return Array.from({ length: 7 }, (_, i) =>
    monday.add({ days: i }).toDate(tz).toLocaleDateString('id-ID', { weekday: 'short' }),
  )
})

const monthTitle = computed(
  () => new CalendarDate(vy.value, vm.value, 1).toDate(tz).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }),
)

/* ── Navigasi bulan ────────────────────────────────────────────────────── */
function canGoPrev(): boolean {
  const min = getMin()
  return vy.value > min.year || (vy.value === min.year && vm.value > min.month)
}

/** Batas navigasi: maksimal 3 bulan ke depan dari hari ini. */
function maxVisible(): CalendarDate {
  const max = todayValue.add({ months: 3 })
  return new CalendarDate(max.year, max.month, 1)
}

function canGoNext(): boolean {
  const max = maxVisible()
  return vy.value < max.year || (vy.value === max.year && vm.value < max.month)
}

function goMonth(delta: number) {
  if (delta > 0 && !canGoNext()) return
  if (delta < 0 && !canGoPrev()) return
  let y = vy.value
  let m = vm.value + delta
  if (m < 1) {
    y--
    m = 12
  } else if (m > 12) {
    y++
    m = 1
  }
  vy.value = y
  vm.value = m
  emitVisibleMonth()
}

/* ── Ketersediaan per tanggal ──────────────────────────────────────────── */
function isDisabledGd(gd: GridDay): boolean {
  if (!gd.inMonth) return true
  const d = parseIso(gd.iso)
  if (d.compare(todayValue) < 0 || d.compare(getMin()) < 0) return true
  // Tanggal tanpa data = disabled. User hanya bisa memilih tanggal yang
  // sistem tahu pasti punya slot terbuka — tidak ada lagi klik-lalu-kosong.
  const info = props.availability[gd.iso]
  return !info || !info.available
}

function tooltipGd(gd: GridDay): string {
  const d = parseIso(gd.iso)
  if (d.compare(todayValue) < 0 || d.compare(getMin()) < 0) return 'Tanggal sudah lewat'
  const info = props.availability[gd.iso]
  if (!info || !info.available) return 'Tidak tersedia'
  return `Tersedia · ${info.slots} slot · mulai ${info.first_start}`
}

/* ── State visual ──────────────────────────────────────────────────────── */
function isTodayGd(gd: GridDay): boolean {
  return gd.iso === todayIso
}

function isSelectedGd(gd: GridDay): boolean {
  return props.modelValue === gd.iso
}

function select(gd: GridDay) {
  if (isDisabledGd(gd)) return
  emit('update:modelValue', gd.iso)
  emit('change', gd.iso)
}

const selectedLabel = computed(() =>
  props.modelValue ? df.format(parseIso(props.modelValue).toDate(tz)) : null,
)
</script>

<template>
  <div class="rounded-2xl border border-[var(--line)] bg-[var(--surface)] p-4">
    <!-- Header: judul bulan + navigasi -->
    <div class="flex items-center justify-between">
      <button
        type="button"
        class="flex h-8 w-8 items-center justify-center rounded-lg border border-[var(--line)] text-[var(--muted)] transition hover:border-[var(--accent)]/50 hover:text-[var(--accent)] disabled:pointer-events-none disabled:opacity-30"
        :disabled="!canGoPrev()"
        aria-label="Bulan sebelumnya"
        @click="goMonth(-1)"
      >
        <ChevronLeftIcon class="h-4 w-4" />
      </button>

      <h2 class="text-sm font-semibold tracking-tight text-[var(--text)]">{{ monthTitle }}</h2>

      <button
        type="button"
        class="flex h-8 w-8 items-center justify-center rounded-lg border border-[var(--line)] text-[var(--muted)] transition hover:border-[var(--accent)]/50 hover:text-[var(--accent)] disabled:pointer-events-none disabled:opacity-30"
        :disabled="!canGoNext()"
        aria-label="Bulan berikutnya"
        @click="goMonth(1)"
      >
        <ChevronRightIcon class="h-4 w-4" />
      </button>
    </div>

    <!-- Grid kalender LEBAR: 7 kolom melebar penuh mengikuti kolom layout -->
    <div class="mt-3">
      <div class="grid grid-cols-7 gap-1.5">
        <div
          v-for="wd in weekdayLabels"
          :key="wd"
          class="pb-1 text-center text-[10px] font-semibold uppercase tracking-wider text-[var(--muted)]"
        >
          {{ wd }}
        </div>
      </div>

      <div class="grid grid-cols-7 gap-1.5">
        <template v-for="(week, wi) in grid" :key="`week-${wi}`">
          <button
            v-for="gd in week"
            :key="gd.iso"
            type="button"
            class="flex h-10 items-center justify-center rounded-lg text-[13px] tabular-nums transition-all duration-150"
            :class="[
              !gd.inMonth
                ? 'pointer-events-none opacity-0'
                : isDisabledGd(gd)
                  ? 'cursor-not-allowed text-[var(--text)]/30'
                  : isSelectedGd(gd)
                    ? 'bg-[var(--accent)] font-semibold text-white shadow-sm'
                    : 'text-[var(--text)] hover:bg-[var(--accent)]/10',
              isTodayGd(gd) && !isSelectedGd(gd) && !isDisabledGd(gd)
                ? 'ring-1 ring-inset ring-[var(--accent)]/40'
                : '',
            ]"
            :disabled="isDisabledGd(gd)"
            :aria-label="tooltipGd(gd)"
            :title="tooltipGd(gd)"
            @click="select(gd)"
          >
            {{ gd.day }}
          </button>
        </template>
      </div>
    </div>

    <!-- Footer: tanggal terpilih + hint -->
    <div class="mt-3 flex items-center justify-between border-t border-[var(--line)] pt-2.5">
      <p class="text-[11px] text-[var(--muted)]">
        <span v-if="selectedLabel" class="font-medium text-[var(--text)]">{{ selectedLabel }}</span>
        <template v-else>Pilih tanggal</template>
      </p>
      <p class="text-[10px] text-[var(--muted)]/70">Hover tanggal untuk detail ketersediaan</p>
    </div>
  </div>
</template>
