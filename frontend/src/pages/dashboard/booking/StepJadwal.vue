<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { apiFetch } from '../../../lib/api'
import { useBookingStore } from '../../../stores/booking'
import BaseButton from '../../../components/ui/BaseButton.vue'

const router = useRouter()
const store = useBookingStore()

const isSubmitting = ref(false)
const error = ref('')
const canConfirm = ref(false)

const today = new Date()
today.setDate(today.getDate() + 1)
if (!store.bookingDate) {
  store.bookingDate = today.toISOString().split('T')[0]
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

watch(
  () => store.psikolog?.id,
  (id) => {
    if (id) fetchSlots()
  },
  { immediate: true },
)

watch(
  () => store.selectedSlot,
  (slot) => {
    canConfirm.value = !!slot
  },
)

onMounted(() => {
  // Durasi/tanggal berubah sejak langkah 1 → refresh slot
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

    <section>
      <h2 class="text-sm font-semibold text-[var(--text)]">Pilih Tanggal Sesi</h2>
      <input
        v-model="store.bookingDate"
        type="date"
        class="field-input mt-3 sm:max-w-xs"
        @change="fetchSlots"
      />
    </section>

    <section>
      <h2 class="text-sm font-semibold text-[var(--text)]">Slot Waktu Tersedia</h2>
      <p class="mt-0.5 text-xs text-[var(--muted)]">
        Slot menyesuaikan jadwal praktek psikolog dan durasi {{ store.durationMinutes }} menit.
      </p>

      <div v-if="store.loadingSlots" class="mt-3 grid gap-2.5 sm:grid-cols-3 xl:grid-cols-4">
        <div v-for="i in 8" :key="i" class="h-16 animate-pulse rounded-xl bg-[var(--muted)]/10" />
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

    <!-- Info proses -->
    <section class="rounded-xl bg-sky-500/8 px-4 py-3.5 text-xs leading-relaxed text-sky-700 dark:text-sky-400">
      Setelah Anda kirim, pengajuan berstatus <strong>menunggu persetujuan psikolog</strong>.
      Slot akan ditahan untuk Anda dan notifikasi WhatsApp dikirim ke psikolog.
      Anda akan diberi tahu saat pengajuan disetujui.
    </section>

    <div class="flex items-center justify-end rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4">
      <BaseButton size="md" :disabled="!canConfirm || isSubmitting" @click="submitBooking">
        {{ isSubmitting ? 'Mengirim Pengajuan…' : 'Kirim Pengajuan Jadwal' }}
      </BaseButton>
    </div>
  </div>
</template>
