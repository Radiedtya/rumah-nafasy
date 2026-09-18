<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { apiFetch } from '../../../lib/api'
import { useBookingStore } from '../../../stores/booking'
import BaseButton from '../../../components/ui/BaseButton.vue'

const router = useRouter()
const store = useBookingStore()

const isSubmitting = ref(false)
const error = ref('')

const order = computed(() => store.order)
const canConfirm = computed(() => !!store.selectedSlot && !isSubmitting.value)

// Order belum ada (refresh langsung ke sini) → kembali ke langkah 1
if (!order.value) {
  router.replace(
    store.psikolog?.slug
      ? `/dashboard/booking/${store.psikolog.slug}`
      : '/dashboard/psikolog',
  )
}

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
    const durMinutes = store.selectedDuration?.minutes || 60
    const res = await apiFetch(
      `pasien/psikolog/${store.psikolog.id}/slots?date=${store.bookingDate}&duration_minutes=${durMinutes}`,
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

// Muat slot saat masuk halaman; muat ulang saat tanggal berubah
watch(
  () => store.psikolog?.id,
  (id) => {
    if (id && order.value) fetchSlots()
  },
  { immediate: true },
)

async function confirmSchedule() {
  if (!store.selectedSlot) {
    error.value = 'Silakan pilih slot waktu terlebih dahulu'
    return
  }

  isSubmitting.value = true
  error.value = ''

  try {
    const res = await apiFetch(`pasien/orders/${order.value.id}/schedule`, {
      method: 'POST',
      body: JSON.stringify({
        booking_date: store.bookingDate,
        start_time: store.selectedSlot.start_time,
      }),
    })
    store.confirmedBooking = res.data
    router.push(`/dashboard/booking/${store.psikolog.slug}/selesai`)
  } catch (err: any) {
    error.value = err.message || 'Gagal memilih jadwal'
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
        Slot menyesuaikan jadwal praktek psikolog dan durasi {{ store.selectedDuration?.name ?? 'sesi' }}.
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
          class="rounded-xl border p-3.5 text-center tabular-nums transition-all"
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

    <div class="flex items-center justify-end rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4">
      <BaseButton size="md" :disabled="!canConfirm" @click="confirmSchedule">
        {{ isSubmitting ? 'Memproses Reservasi…' : 'Konfirmasi Jadwal Konsultasi' }}
      </BaseButton>
    </div>
  </div>
</template>
