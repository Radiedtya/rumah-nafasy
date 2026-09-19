<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import {
  DateFormatter,
  parseDate,
  today,
  getLocalTimeZone,
  type DateValue,
} from '@internationalized/date'
import { CalendarDaysIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

/**
 * Kalender pilih tanggal sesi — UI custom (bukan input date bawaan HTML).
 * Dibangun di atas primitif @internationalized/date (mesin yang sama dengan
 * Calendar reka-ui) dengan locale Indonesia & pekan mulai Senin.
 */

interface Props {
  /** Tanggal terpilih dalam format "Y-m-d" (bisa kosong). */
  modelValue: string
  /** Tanggal paling awal yang boleh dipilih (default: besok). */
  minDate?: string
}

const props = withDefaults(defineProps<Props>(), { minDate: '' })

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'change', value: string): void
}>()

const tz = getLocalTimeZone()
const minDateValue = computed(() =>
  props.minDate ? parseDate(props.minDate) : today(tz).add({ days: 1 }),
)

// ── Bulan yang sedang ditampilkan ────────────────────────────────────────────
function initialView(): DateValue {
  const fromValue = props.modelValue ? parseDate(props.modelValue) : null
  return fromValue && fromValue.compare(minDateValue.value) >= 0
    ? fromValue
    : minDateValue.value
}

const view = ref(initialView())
watch(
  () => props.modelValue,
  (v) => {
    if (v && parseDate(v).compare(view.value) < 0) {
      // terpilih di luar bulan tampilan (mis. reset) → geser tampilan
      view.value = parseDate(v)
    }
  },
)

const viewMonth = computed(() =>
  view.value.set({ day: 1 }),
)

const monthLabel = computed(() =>
  new DateFormatter('id-ID', { month: 'long', year: 'numeric' }).format(
    viewMonth.value.toDate(tz),
  ),
)

/** Matriks 6 pekan × 7 hari, `null` = sel kosong di luar bulan. */
const weeks = computed<(DateValue | null)[][]>(() => {
  const first = viewMonth.value
  // JS: 0=Minggu..6=Sabtu → geser agar pekan mulai Senin
  const jsDow = first.toDate(tz).getDay()
  const offset = (jsDow + 6) % 7
  const gridStart = first.subtract({ days: offset })

  const rows: (DateValue | null)[][] = []
  let cursor = gridStart
  for (let w = 0; w < 6; w++) {
    const row: (DateValue | null)[] = []
    for (let d = 0; d < 7; d++) {
      row.push(cursor.month === first.month ? cursor : null)
      cursor = cursor.add({ days: 1 })
    }
    rows.push(row)
  }
  return rows
})

const weekLabels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']

const selectedValue = computed(() =>
  props.modelValue ? parseDate(props.modelValue) : null,
)
const todayValue = today(tz)

function isDisabled(date: DateValue): boolean {
  return date.compare(minDateValue.value) < 0
}

function dayLabel(date: DateValue): string {
  return new DateFormatter('id-ID', { day: 'numeric' }).format(date.toDate(tz))
}

function onSelect(date: DateValue) {
  if (isDisabled(date)) return
  emit('update:modelValue', date.toString())
  emit('change', date.toString())
}

function prevMonth() {
  const target = viewMonth.value.subtract({ months: 1 }).set({ day: 1 })
  if (target.compare(minDateValue.value.set({ day: 1 })) < 0) return
  view.value = target
}

function nextMonth() {
  view.value = viewMonth.value.add({ months: 1 }).set({ day: 1 })
}

const canPrev = computed(
  () => viewMonth.value.subtract({ months: 1 }).set({ day: 1 }).compare(minDateValue.value.set({ day: 1 })) >= 0,
)

const selectedLabel = computed(() => {
  if (!selectedValue.value) return 'Pilih tanggal sesi'
  return new DateFormatter('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(selectedValue.value.toDate(tz))
})
</script>

<template>
  <div class="rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4">
    <!-- Header bulan -->
    <div class="mb-3 flex items-center justify-between">
      <button
        type="button"
        class="flex h-8 w-8 items-center justify-center rounded-lg text-[var(--muted)] transition-colors hover:bg-[var(--muted)]/10 hover:text-[var(--text)] focus-visible:ring-2 focus-visible:ring-[var(--accent)]/30 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-30"
        :disabled="!canPrev"
        aria-label="Bulan sebelumnya"
        @click="prevMonth"
      >
        <ChevronLeftIcon class="h-4 w-4" />
      </button>
      <p class="text-sm font-semibold capitalize text-[var(--text)]">{{ monthLabel }}</p>
      <button
        type="button"
        class="flex h-8 w-8 items-center justify-center rounded-lg text-[var(--muted)] transition-colors hover:bg-[var(--muted)]/10 hover:text-[var(--text)] focus-visible:ring-2 focus-visible:ring-[var(--accent)]/30 focus-visible:outline-none"
        aria-label="Bulan berikutnya"
        @click="nextMonth"
      >
        <ChevronRightIcon class="h-4 w-4" />
      </button>
    </div>

    <!-- Grid tanggal -->
    <div class="select-none">
      <div class="mb-1 grid grid-cols-7 gap-1">
        <span
          v-for="lbl in weekLabels"
          :key="lbl"
          class="pb-1 text-center text-[10px] font-semibold uppercase tracking-wide text-[var(--muted)]"
        >
          {{ lbl }}
        </span>
      </div>

      <div
        v-for="(week, wi) in weeks"
        :key="wi"
        class="mb-1 grid grid-cols-7 gap-1"
      >
        <div v-for="(date, di) in week" :key="`${wi}-${di}`" class="flex">
          <button
            v-if="date"
            type="button"
            class="flex h-10 w-full items-center justify-center rounded-lg text-xs font-medium transition-colors focus-visible:ring-2 focus-visible:ring-[var(--accent)]/40 focus-visible:outline-none"
            :class="
              selectedValue && date.compare(selectedValue) === 0
                ? 'bg-[var(--accent)] font-semibold text-white hover:bg-[var(--accent)]'
                : date.compare(todayValue) === 0
                  ? 'font-bold text-[var(--accent)] hover:bg-[var(--muted)]/10'
                  : isDisabled(date)
                    ? 'cursor-not-allowed text-[var(--muted)]/30'
                    : 'text-[var(--text)] hover:bg-[var(--muted)]/10'
            "
            :disabled="isDisabled(date)"
            :aria-pressed="!!(selectedValue && date.compare(selectedValue) === 0)"
            :aria-label="dayLabel(date)"
            @click="onSelect(date)"
          >
            {{ date.day }}
          </button>
          <span v-else class="h-10 w-full" />
        </div>
      </div>
    </div>

    <!-- Hasil pilihan -->
    <p class="mt-3 flex items-center gap-1.5 border-t border-[var(--line)] pt-2.5 text-xs text-[var(--muted)]">
      <CalendarDaysIcon class="h-3.5 w-3.5 shrink-0 text-[var(--accent)]" />
      <span class="font-medium text-[var(--text)]">{{ selectedLabel }}</span>
    </p>
  </div>
</template>
