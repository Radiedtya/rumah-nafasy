<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { apiFetch } from '../../../lib/api'
import { useBookingStore } from '../../../stores/booking'
import { BanknotesIcon } from '@heroicons/vue/24/outline'
import BaseButton from '../../../components/ui/BaseButton.vue'
import BookingCalendar from '../../../components/dashboard/booking/BookingCalendar.vue'

const router = useRouter()
const store = useBookingStore()

const isSubmitting = ref(false)
const error = ref('')

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

watch(
  () => store.psikolog?.id,
  (id) => {
    if (id && store.bookingDate) fetchSlots()
  },
  { immediate: true },
)

// Tanggal dipilih di kalender → muat slot
function onDateChange() {
  fetchSlots()
}

onMounted(() => {
  if (!store.bookingDate) {
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    store.bookingDate = tomorrow.toISOString().split('T')[0]
  }
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
        note: store.note || null,
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

    <div class="grid items-start gap-5 lg:grid-cols-[320px_minmax(0,1fr)]">
      <!-- Kalender custom -->
      <BookingCalendar
        v-model="store.bookingDate"
        @change="onDateChange"
      />

      <!-- Slot waktu -->
      <section>
        <h2 class="text-sm font-semibold text-[var(--text)]">Slot Waktu Tersedia</h2>
        <p class="mt-0.5 text-xs text-[var(--muted)]">
          Slot menyesuaikan jadwal praktek psikolog dan durasi {{ store.durationMinutes }} menit.
        </p>

        <div v-if="store.loadingSlots" class="mt-3 grid gap-2.5 sm:grid-cols-3 xl:grid-cols-4">
          <div v-for="i in 8" :key="i" class="h-16 animate-pulse rounded-xl bg-[var(--muted)]/10" />
        </div>

        <div
          v-else-if="!store.bookingDate"
          class="mt-3 rounded-xl bg-[var(--muted)]/6 px-4 py-6 text-center text-xs text-[var(--muted)]"
        >
          Pilih tanggal di kalender untuk melihat slot tersedia.
        </div>

        <div
          v-else-if="store.slots.length === 0"
          class="mt-3 rounded-xl bg-amber-500/10 px-4 py-6 text-center text-xs text-amber-600 dark:text-amber-400"
        >
          Tidak ada slot tersedia di tanggal ini. Silakan pilih hari lain.
        </div>

        <div v-else class="mt-3 grid gap-2.5 sm:grid-cols-3 xl:grid-cols-4">
          <button
            v-for="slot in store.slots"
            :key="slot.start_time"
            type="button"
            class="rounded-xl border p-3.5 text-center tabular-nums transition-colors"
            :class="
              store.selectedSlot?.start_time === slot.start_time
                ? 'border-[var(--accent)] bg-[var(--accent)]/8 ring-1 ring-[var(--accent)]/40'
                : 'border-[var(--line)] hover:bg-[var(--muted)]/6'
            "
            @click="store.selectedSlot = slot"
          >
            <p class="text-sm font-semibold text-[var(--text)]">{{ slot.start_time }}</p>
            <p class="mt-0.5 text-[11px] text-[var(--muted)]">s/d {{ slot.end_time }}</p>
          </button>
        </div>
      </section>
    </div>

    <!-- Info proses -->
    <section class="rounded-xl bg-sky-500/8 px-4 py-3.5 text-xs leading-relaxed text-sky-700 dark:text-sky-400">
      Setelah Anda kirim, pengajuan berstatus <strong>menunggu persetujuan psikolog</strong>.
      Slot akan ditahan untuk Anda dan notifikasi WhatsApp dikirim ke psikolog.
      Anda akan diberi tahu saat pengajuan disetujui.
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
