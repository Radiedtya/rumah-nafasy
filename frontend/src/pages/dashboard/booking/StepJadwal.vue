<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { apiFetch } from '../../../lib/api'
import { useBookingStore } from '../../../stores/booking'
import { BanknotesIcon, SparklesIcon } from '@heroicons/vue/24/outline'
import BaseButton from '../../../components/ui/BaseButton.vue'
import BookingCalendar, { type CalendarDayAvailability } from '../../../components/dashboard/booking/BookingCalendar.vue'

const router = useRouter()
const store = useBookingStore()

const isSubmitting = ref(false)
const error = ref('')

// ── Ketersediaan per bulan (untuk indikator kalender) ────────────────────
const availability = ref<Record<string, CalendarDayAvailability>>({})

// ── 5 rekomendasi slot terdekat ─────────────────────────────────────────
const recommendations = ref<{ date: string; start_time: string; end_time: string }[]>([])
const loadingRecs = ref(false)

const psikologId = computed(() => store.psikolog?.id)

/** Bulan kalender yang sedang tampil (awal bulan, ISO) — untuk pesan kosong yang akurat. */
const visibleFrom = ref(todayIso())

/** Label rentang yang benar-benar dicek sistem, dipakai pesan kosong rekomendasi. */
const recsRangeLabel = computed(() => {
  const base = visibleFrom.value > todayIso() ? visibleFrom.value : todayIso()
  const [y, m] = base.split('-').map(Number)
  const fmt = (yy: number, mm: number) =>
    new Date(yy, mm - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
  const endY = m + 1 > 12 ? y + 1 : y
  const endM = m + 1 > 12 ? m + 1 - 12 : m + 1
  return `${fmt(y, m)} – ${fmt(endY, endM)}`
})

function applyAvailability(days: CalendarDayAvailability[]) {
  for (const d of days) {
    availability.value = {
      ...availability.value,
      [d.date]: d,
    }
  }
}

/* ── Helper tanggal LOKAL (bukan UTC) ─────────────────────────────────
 * toISOString() memakai UTC — di WIB (UTC+7), jam 00:00–06:59 pagi hari
 * ini terbaca "kemarin". Semua ISO di halaman ini dibuat dari komponen
 * lokal supaya tidak geser hari. */
function toLocalIso(d: Date): string {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

function todayIso(): string {
  return toLocalIso(new Date())
}

/** Request id: respons yang datang terlambat (user sudah pindah bulan) diabaikan. */
let availabilityReqId = 0
/** Guard sederhana: sedang ada fetch availability berjalan (cooldown geser bulan). */
let availabilityInFlight = false
/** Error ketersediaan — ditampilkan sebagai banner, bukan ditelan diam-diam. */
const availabilityError = ref('')

/**
 * SATU-SATUNYA sumber data ketersediaan (kalender + rekomendasi).
 * Dipicu oleh: watch psikologId (immediate) saat masuk halaman, dan
 * monthChange dari kalender saat user pindah bulan.
 * `from` = awal bulan tampilan; rekomendasi tetap dihitung dari hari ini.
 */
function fetchAvailability(from: string): Promise<void> {
  if (!psikologId.value) return Promise.resolve()

  // Cooldown: geser bulan cepat tidak boleh memicu storm request
  if (visibleFrom.value === from && availabilityInFlight) return Promise.resolve()
  const reqId = ++availabilityReqId
  visibleFrom.value = from
  loadingRecs.value = true
  availabilityInFlight = true
  availabilityError.value = ''

  return (async () => {
    try {
      // Rentang: awal bulan tampilan s/d akhir bulan berikutnya (cukup utk
      // kalender + rekomendasi 2 bulan ke depan)
      const [y, m] = from.split('-').map(Number)
      const to = toLocalIso(new Date(y, m + 1, 0))

      const res = await apiFetch<{
        days: CalendarDayAvailability[]
        next: { date: string; start_time: string; end_time: string }[]
      }>(
        `pasien/psikolog/${psikologId.value}/availability?from=${from}&to=${to}&duration_minutes=${store.durationMinutes}`,
      )

      // Respons basi: user sudah pindah bulan lain sebelum respons tiba
      if (reqId !== availabilityReqId) return

      applyAvailability(res.data.days || [])

      // Rekomendasi = slot terdekat DARI HARI INI. Hanya di-refresh saat
      // rentang mencakup bulan berjalan — pindah bulan ke depan tidak boleh
      // mengosongkan rekomendasi yang sudah benar.
      if (new Date(`${from}T00:00:00`) <= new Date(`${todayIso()}T00:00:00`)) {
        recommendations.value = res.data.next || []
      }

      // Tanggal terpilih ternyata tidak tersedia (mis. default besok penuh)
      // → pilih otomatis rekomendasi pertama agar slot langsung tampil.
      // Jangan "yank" saat user sedang memilih/slot sedang dimuat.
      const info = availability.value[store.bookingDate]
      if (
        reqId === availabilityReqId &&
        recommendations.value.length > 0 &&
        info &&
        !info.available &&
        !store.loadingSlots &&
        store.slots.length === 0
      ) {
        store.bookingDate = recommendations.value[0].date
        await fetchSlots()
      }
    } catch (e: any) {
      console.error('Failed fetching availability', e)
      // 401 = sesi habis (localStorage token dibersihkan apiFetch).
      // Recovery jelas: ke login dengan redirect kembali ke sini.
      if (e?.status === 401) {
        error.value = 'Sesi Anda berakhir. Silakan login kembali.'
        setTimeout(() => {
          router.push({
            path: '/login',
            query: { redirect: router.currentRoute.value.fullPath },
          })
        }, 1200)
        return
      }
      // Gagal lain: pesan jujur + tanggal tanpa data otomatis disabled.
      availabilityError.value = 'Gagal memuat ketersediaan jadwal. '
        + (e?.message || 'Cek koneksi Anda.')
    } finally {
      if (reqId === availabilityReqId) {
        loadingRecs.value = false
        availabilityInFlight = false
      }
    }
  })()
}

async function fetchSlots() {
  if (!store.psikolog || !store.bookingDate) return
  store.loadingSlots = true
  store.slots = []
  store.selectedSlot = null
  error.value = ''

  try {
    const res = await apiFetch(
      `pasien/psikolog/${store.psikolog.id}/slots?date=${store.bookingDate}&duration_minutes=${store.durationMinutes}`,
    )
    store.slots = res.data?.available_slots || []
    if (store.slots.length > 0) {
      store.selectedSlot = store.slots[0]
    }
  } catch (err: any) {
    error.value = err.message || 'Gagal memuat jadwal'
  } finally {
    store.loadingSlots = false
  }
}

/**
 * Sumber data availability: satunya-satunya jalan masuk adalah watch ini.
 * `immediate` penting — store.psikolog biasanya SUDAH terisi saat halaman
 * dibuka (persist dari langkah 1), jadi tanpa immediate fetch tak pernah jalan
 * dan kalender/rekomendasi tampak kosong palsu (bug yang sudah diperbaiki).
 */
watch(
  psikologId,
  (id) => {
    if (id) {
      availability.value = {}
      availabilityError.value = ''
      recommendations.value = []
      fetchAvailability(todayIso())
    }
  },
  { immediate: true },
)

// Tanggal dipilih dari kalender → muat slot tanggal itu
function onDateChange() {
  fetchSlots()
}

// Bulan kalender diganti → fetch ketersediaan bulan itu (rekomendasi tetap)
function onMonthChange(from: string) {
  visibleFrom.value = from
  fetchAvailability(from)
}

/** Pilih tanggal dari rekomendasi lalu muat slotnya. */
function pickRecommendation(rec: { date: string; start_time: string }) {
  store.bookingDate = rec.date
  fetchSlots().then(() => {
    const match = store.slots.find((s) => s.start_time === rec.start_time)
    store.selectedSlot = match ?? store.slots[0] ?? null
  })
}

const dateLabel = (iso: string) =>
  new Date(`${iso}T00:00:00`).toLocaleDateString('id-ID', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
  })

onMounted(() => {
  if (!store.bookingDate) {
    // Default besok — konsisten dengan batas minimum kalender
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    store.bookingDate = toLocalIso(tomorrow)
  }
  // Slot tanggal terpilih; ketersediaan bulan berjalan via watch psikologId
  // + emit monthChange awal dari kalender (lihat BookingCalendar).
  fetchSlots()
})

async function submitBooking() {
  if (!store.selectedSlot || !store.psikolog) {
    error.value = 'Silakan pilih slot waktu terlebih dahulu'
    return
  }

  isSubmitting.value = true
  error.value = ''

  try {
    const res = await apiFetch('pasien/bookings', {
      method: 'POST',
      body: JSON.stringify({
        psikolog_id: store.psikolog.id,
        consultation_type: store.consultationType,
        duration_minutes: store.durationMinutes,
        requested_category_id: store.requestedCategory?.id ?? null,
        booking_date: store.bookingDate,
        start_time: store.selectedSlot.start_time,
        note: null,
        complaint_markdown: store.complaint || null,
      }),
    })
    store.confirmedBooking = res.data
    router.push(`/dashboard/booking/${store.psikolog.slug}/selesai`)
  } catch (err: any) {
    error.value = err.message || 'Gagal mengirim pengajuan'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <div v-if="error" class="rounded-lg bg-rose-500/10 px-3.5 py-2.5 text-xs text-rose-600 dark:text-rose-400">
      {{ error }}
    </div>

    <div
      v-if="availabilityError"
      class="flex items-center justify-between rounded-lg bg-amber-500/10 px-3.5 py-2.5 text-xs text-amber-700 dark:text-amber-400"
    >
      <span>{{ availabilityError }}</span>
      <button
        type="button"
        class="ml-3 shrink-0 font-semibold underline underline-offset-2 hover:opacity-80"
        @click="fetchAvailability(visibleFrom)"
      >
        Coba lagi
      </button>
    </div>

    <!-- ═══ 2 KOLOM: kalender | (rekomendasi + slot waktu) ═══ -->
    <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)]">
      <!-- ── KOLOM KALENDER ── -->
      <BookingCalendar
        v-model="store.bookingDate"
        :availability="availability"
        @change="onDateChange"
        @month-change="onMonthChange"
      />

      <!-- ── KOLOM KANAN: Rekomendasi (atas) + Slot Waktu (bawah) ── -->
      <div class="space-y-5 lg:sticky lg:top-4">
        <!-- Rekomendasi terdekat — tepat di atas panel Slot Waktu -->
        <section class="rounded-2xl border border-[var(--line)] bg-[var(--surface)] p-4">
          <div class="flex items-center gap-1.5">
            <SparklesIcon class="h-4 w-4 text-[var(--accent)]" />
            <h2 class="text-sm font-semibold tracking-tight text-[var(--text)]">Rekomendasi Terdekat</h2>
            <span class="ml-auto text-[10px] text-[var(--muted)]">{{ store.durationMinutes }} menit</span>
          </div>

          <div v-if="loadingRecs && recommendations.length === 0" class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
            <div v-for="i in 3" :key="i" class="h-[58px] animate-pulse rounded-xl bg-[var(--muted)]/10" />
          </div>

          <div
            v-else-if="recommendations.length === 0"
            class="mt-3 rounded-xl bg-amber-500/10 px-3 py-4 text-center text-[11px] text-amber-600 dark:text-amber-400"
          >
            Belum ada slot terbuka pada {{ recsRangeLabel }}.
          </div>

          <div v-else class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
            <button
              v-for="(rec, i) in recommendations"
              :key="`${rec.date}-${rec.start_time}`"
              type="button"
              class="flex min-h-[58px] flex-col justify-between rounded-xl border p-2.5 text-left transition-all duration-150"
              :class="
                store.bookingDate === rec.date && store.selectedSlot?.start_time === rec.start_time
                  ? 'border-[var(--accent)] bg-[var(--accent)]/8 ring-1 ring-[var(--accent)]/40'
                  : 'border-[var(--line)] hover:border-[var(--accent)]/40 hover:bg-[var(--accent)]/5'
              "
              @click="pickRecommendation(rec)"
            >
              <span class="flex w-full items-center gap-1 text-[10px] font-semibold text-[var(--muted)]">
                {{ dateLabel(rec.date) }}
                <span
                  v-if="i === 0"
                  class="ml-auto rounded bg-emerald-500/10 px-1 py-0.5 text-[8px] font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-400"
                >
                  Terdekat
                </span>
              </span>
              <span class="text-[13px] font-semibold tabular-nums tracking-tight text-[var(--text)]">
                {{ rec.start_time }}
                <span class="text-[10px] font-normal text-[var(--muted)]">– {{ rec.end_time }}</span>
              </span>
            </button>
          </div>

          <p class="mt-2.5 text-[10px] text-[var(--muted)]">
            Klik untuk langsung memilih, atau atur sendiri di kalender di samping kiri.
          </p>
        </section>

        <!-- Slot Waktu — di bawah rekomendasi -->
        <section class="rounded-2xl border border-[var(--line)] bg-[var(--surface)] p-5">
        <h2 class="text-sm font-semibold tracking-tight text-[var(--text)]">Slot Waktu Tersedia</h2>
        <p class="mt-0.5 text-xs text-[var(--muted)]">
          {{ store.psikolog?.name?.split(' ').slice(0, 2).join(' ') }} · durasi {{ store.durationMinutes }} menit
        </p>

        <div v-if="store.loadingSlots" class="mt-4 grid grid-cols-2 gap-2.5">
          <div v-for="i in 4" :key="i" class="h-[68px] animate-pulse rounded-xl bg-[var(--muted)]/10" />
        </div>

        <div
          v-else-if="!store.bookingDate"
          class="mt-4 rounded-xl bg-[var(--muted)]/6 px-4 py-8 text-center text-xs text-[var(--muted)]"
        >
          Pilih tanggal di kalender untuk melihat slot tersedia.
        </div>

        <div
          v-else-if="store.slots.length === 0"
          class="mt-4 rounded-xl bg-amber-500/10 px-4 py-8 text-center text-xs text-amber-600 dark:text-amber-400"
        >
          Tidak ada slot tersedia di tanggal ini. Silakan pilih hari lain.
        </div>

        <div v-else class="mt-4 grid grid-cols-2 gap-2.5">
          <button
            v-for="slot in store.slots"
            :key="slot.start_time"
            type="button"
            class="rounded-xl border p-3.5 text-center tabular-nums transition-all duration-150"
            :class="
              store.selectedSlot?.start_time === slot.start_time
                ? 'border-[var(--accent)] bg-[var(--accent)]/8 ring-1 ring-[var(--accent)]/40'
                : 'border-[var(--line)] hover:border-[var(--accent)]/40 hover:bg-[var(--accent)]/5'
            "
            @click="store.selectedSlot = slot"
          >
            <p class="text-sm font-semibold tabular-nums text-[var(--text)]">{{ slot.start_time }}</p>
            <p class="mt-0.5 text-[11px] tabular-nums text-[var(--muted)]">s/d {{ slot.end_time }}</p>
          </button>
        </div>

        <p class="mt-4 border-t border-[var(--line)] pt-3 text-[11px] leading-relaxed text-[var(--muted)]">
          Slot ditahan setelah pengajuan dikirim sampai psikolog memutuskan.
        </p>
      </section>
      </div>
    </div>

    <!-- Info proses -->
    <section class="rounded-xl bg-sky-500/8 px-4 py-3.5 text-xs leading-relaxed text-sky-700 dark:text-sky-400">
      Setelah Anda kirim, pengajuan berstatus <strong>menunggu persetujuan psikolog</strong>.
      Notifikasi WhatsApp dikirim ke psikolog dan Anda diberi tahu saat disetujui.
    </section>

    <!-- Catatan bayar nanti -->
    <section class="flex items-start gap-2.5 rounded-xl border border-emerald-500/30 bg-emerald-500/8 p-4">
      <BanknotesIcon class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
      <p class="text-xs leading-relaxed text-emerald-800 dark:text-emerald-300">
        <strong>Konsultasi sekarang, bayar nanti.</strong>
        Pembayaran dilakukan langsung ke psikolog setelah sesi selesai — nominal dapat
        menyesuaikan durasi riil sesi.
      </p>
    </section>

    <div class="flex items-center justify-end rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4">
      <BaseButton size="md" :disabled="!store.selectedSlot || isSubmitting" @click="submitBooking">
        {{ isSubmitting ? 'Mengirim Pengajuan…' : 'Kirim Pengajuan Jadwal' }}
      </BaseButton>
    </div>
  </div>
</template>
