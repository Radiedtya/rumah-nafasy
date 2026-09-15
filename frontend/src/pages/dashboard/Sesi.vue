<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import {
  CalendarDaysIcon,
  ClockIcon,
  VideoCameraIcon,
  ArrowPathIcon,
  XCircleIcon,
  XMarkIcon,
  ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/20/solid'
import { apiFetch } from '../../lib/api'

const loading = ref(true)
const bookings = ref<any[]>([])
const activeTab = ref<'all' | 'upcoming' | 'completed' | 'cancelled'>('upcoming')

// Modal Reschedule
const rescheduleModalOpen = ref(false)
const selectedBooking = ref<any>(null)
const rescheduleDate = ref('')
const rescheduleSlots = ref<any[]>([])
const selectedNewSlot = ref<any>(null)
const rescheduleReason = ref('')
const loadingRescheduleSlots = ref(false)
const isSubmittingReschedule = ref(false)
const rescheduleError = ref('')

// Modal Cancel
const cancelModalOpen = ref(false)
const cancelBookingData = ref<any>(null)
const cancelReason = ref('')
const isSubmittingCancel = ref(false)
const cancelError = ref('')

async function fetchBookings() {
  loading.value = true
  try {
    const res = await apiFetch('pasien/bookings')
    bookings.value = res.data?.data || res.data || []
  } catch (e) {
    console.error('Failed fetching bookings', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchBookings()
})

const filteredBookings = computed(() => {
  if (activeTab.value === 'upcoming') {
    return bookings.value.filter(b => b.status === 'confirmed' || b.status === 'in_progress')
  }
  if (activeTab.value === 'completed') {
    return bookings.value.filter(b => b.status === 'completed')
  }
  if (activeTab.value === 'cancelled') {
    return bookings.value.filter(b => b.status === 'cancelled')
  }
  return bookings.value
})

// Open Reschedule Modal
function openReschedule(booking: any) {
  selectedBooking.value = booking
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 2)
  rescheduleDate.value = tomorrow.toISOString().split('T')[0]
  rescheduleReason.value = ''
  rescheduleError.value = ''
  rescheduleModalOpen.value = true
  loadRescheduleSlots()
}

async function loadRescheduleSlots() {
  if (!selectedBooking.value || !rescheduleDate.value) return
  loadingRescheduleSlots.value = true
  rescheduleSlots.value = []
  selectedNewSlot.value = null

  try {
    const durMinutes = selectedBooking.value.order?.duration_minutes || 60
    const res = await apiFetch(
      `pasien/psikolog/${selectedBooking.value.psikolog.id}/slots?date=${rescheduleDate.value}&duration_minutes=${durMinutes}`
    )
    rescheduleSlots.value = res.data?.available_slots || []
    if (rescheduleSlots.value.length > 0) {
      selectedNewSlot.value = rescheduleSlots.value[0]
    }
  } catch (e: any) {
    rescheduleError.value = e.message || 'Gagal memuat slot'
  } finally {
    loadingRescheduleSlots.value = false
  }
}

async function submitReschedule() {
  if (!selectedNewSlot.value) {
    rescheduleError.value = 'Silakan pilih slot waktu baru'
    return
  }
  isSubmittingReschedule.value = true
  rescheduleError.value = ''

  try {
    await apiFetch(`pasien/bookings/${selectedBooking.value.id}/reschedule`, {
      method: 'PUT',
      body: JSON.stringify({
        booking_date: rescheduleDate.value,
        start_time: selectedNewSlot.value.start_time,
        reason: rescheduleReason.value || 'Reschedule oleh pasien',
      }),
    })
    rescheduleModalOpen.value = false
    await fetchBookings()
  } catch (e: any) {
    rescheduleError.value = e.message || 'Gagal melakukan reschedule'
  } finally {
    isSubmittingReschedule.value = false
  }
}

// Open Cancel Modal
function openCancel(booking: any) {
  cancelBookingData.value = booking
  cancelReason.value = ''
  cancelError.value = ''
  cancelModalOpen.value = true
}

async function submitCancel() {
  isSubmittingCancel.value = true
  cancelError.value = ''

  try {
    await apiFetch(`pasien/bookings/${cancelBookingData.value.id}/cancel`, {
      method: 'POST',
      body: JSON.stringify({
        reason: cancelReason.value || 'Dibatalkan oleh pasien',
      }),
    })
    cancelModalOpen.value = false
    await fetchBookings()
  } catch (e: any) {
    cancelError.value = e.message || 'Gagal membatalkan booking'
  } finally {
    isSubmittingCancel.value = false
  }
}
</script>

<template>
  <div class="max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="font-display text-2xl font-bold text-neutral-900 dark:text-neutral-100">
          Sesi Saya
        </h2>
        <p class="text-neutral-500 text-sm mt-0.5">
          Kelola jadwal konsultasi aktif, akses video call, atau lakukan reschedule sesuai kebijakan resmi.
        </p>
      </div>

      <RouterLink
        to="/dashboard/psikolog"
        class="inline-flex items-center px-4 py-2.5 rounded-2xl bg-rose-500 text-white text-xs font-semibold hover:bg-rose-600 transition-colors shadow-xs self-start sm:self-auto"
      >
        + Booking Sesi Baru
      </RouterLink>
    </div>

    <!-- Tabs -->
    <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 p-1.5 flex items-center gap-1">
      <button
        type="button"
        class="flex-1 py-2 rounded-xl text-xs font-medium transition-all"
        :class="activeTab === 'upcoming' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 font-bold' : 'text-neutral-500 hover:text-neutral-900'"
        @click="activeTab = 'upcoming'"
      >
        Mendatang ({{ bookings.filter(b => b.status === 'confirmed' || b.status === 'in_progress').length }})
      </button>

      <button
        type="button"
        class="flex-1 py-2 rounded-xl text-xs font-medium transition-all"
        :class="activeTab === 'completed' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 font-bold' : 'text-neutral-500 hover:text-neutral-900'"
        @click="activeTab = 'completed'"
      >
        Selesai ({{ bookings.filter(b => b.status === 'completed').length }})
      </button>

      <button
        type="button"
        class="flex-1 py-2 rounded-xl text-xs font-medium transition-all"
        :class="activeTab === 'cancelled' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 font-bold' : 'text-neutral-500 hover:text-neutral-900'"
        @click="activeTab = 'cancelled'"
      >
        Dibatalkan ({{ bookings.filter(b => b.status === 'cancelled').length }})
      </button>

      <button
        type="button"
        class="flex-1 py-2 rounded-xl text-xs font-medium transition-all"
        :class="activeTab === 'all' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 font-bold' : 'text-neutral-500 hover:text-neutral-900'"
        @click="activeTab = 'all'"
      >
        Semua
      </button>
    </div>

    <!-- Bookings List -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="h-36 bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 animate-pulse" />
    </div>

    <div v-else-if="filteredBookings.length === 0" class="bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 p-12 text-center text-neutral-400">
      <div class="text-4xl mb-2">🗓️</div>
      <p class="font-medium text-neutral-700 dark:text-neutral-200">Tidak ada data sesi pada tab ini</p>
      <p class="text-xs text-neutral-500 mt-1">Sesi yang Anda jadwalkan akan muncul di sini.</p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="booking in filteredBookings"
        :key="booking.id"
        class="bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200/80 dark:border-neutral-800 p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6"
      >
        <div class="space-y-3">
          <!-- Status & Order -->
          <div class="flex items-center gap-2">
            <span
              class="text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider"
              :class="{
                'bg-emerald-100 text-emerald-700': booking.status === 'confirmed',
                'bg-sky-100 text-sky-700': booking.status === 'in_progress',
                'bg-neutral-100 text-neutral-700': booking.status === 'completed',
                'bg-rose-100 text-rose-700': booking.status === 'cancelled',
              }"
            >
              {{ booking.status }}
            </span>
            <span class="text-xs text-neutral-400 font-mono">
              {{ booking.order?.order_number }}
            </span>
            <span v-if="booking.reschedule_count > 0" class="text-[11px] text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md font-medium">
              Reschedule: {{ booking.reschedule_count }}/2x
            </span>
          </div>

          <!-- Psikolog info -->
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-400 to-orange-400 text-white font-bold flex items-center justify-center text-base shrink-0 shadow-xs">
              {{ booking.psikolog?.name?.charAt(0) || 'P' }}
            </div>
            <div>
              <h3 class="font-display font-bold text-neutral-900 dark:text-neutral-100 text-base">
                {{ booking.psikolog?.name }}
              </h3>
              <p class="text-xs text-rose-500 font-medium">
                {{ booking.psikolog?.specialization || 'Psikolog Terverifikasi' }}
              </p>
            </div>
          </div>

          <!-- Time & Details -->
          <div class="flex items-center gap-4 text-xs text-neutral-600 dark:text-neutral-400 flex-wrap">
            <span class="inline-flex items-center gap-1.5 font-medium text-neutral-900 dark:text-neutral-100">
              <CalendarDaysIcon class="w-4 h-4 text-neutral-400" />
              {{ booking.booking_date }}
            </span>
            <span class="inline-flex items-center gap-1.5 font-medium text-rose-600">
              <ClockIcon class="w-4 h-4 text-rose-400" />
              {{ booking.start_time }} - {{ booking.end_time }}
            </span>
            <span>({{ booking.order?.duration_name || '60 Menit' }})</span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 shrink-0">
          <!-- Join Video Room -->
          <a
            v-if="booking.room_id && (booking.status === 'confirmed' || booking.status === 'in_progress')"
            :href="`https://meet.jit.si/${booking.room_id}`"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition-colors shadow-xs"
          >
            <VideoCameraIcon class="w-4 h-4" />
            <span>Masuk Video Call</span>
            <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
          </a>

          <!-- Reschedule Button -->
          <button
            v-if="booking.status === 'confirmed' && booking.can_reschedule"
            type="button"
            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-neutral-200 dark:border-neutral-800 text-xs font-semibold text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors"
            @click="openReschedule(booking)"
          >
            <ArrowPathIcon class="w-3.5 h-3.5 text-neutral-400" />
            Reschedule
          </button>

          <!-- Cancel Button -->
          <button
            v-if="booking.status === 'confirmed' && booking.can_cancel"
            type="button"
            class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors"
            @click="openCancel(booking)"
          >
            <XCircleIcon class="w-3.5 h-3.5 text-rose-400" />
            Batalkan
          </button>
        </div>
      </div>
    </div>

    <!-- ================= RESCHEDULE MODAL ================= -->
    <div
      v-if="rescheduleModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
    >
      <div class="bg-white dark:bg-neutral-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-neutral-200 dark:border-neutral-800 space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
          <h3 class="font-display text-base font-bold text-neutral-900 dark:text-neutral-100">
            Reschedule Jadwal Konsultasi
          </h3>
          <button type="button" class="p-1 text-neutral-400 hover:text-neutral-600" @click="rescheduleModalOpen = false">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <div v-if="rescheduleError" class="p-3 rounded-xl bg-rose-50 text-rose-700 text-xs">
          {{ rescheduleError }}
        </div>

        <div class="text-xs text-neutral-500">
          Sesuai kebijakan Rumah Natasy, reschedule dapat dilakukan maksimal 2x dan minimal 24 jam sebelum jadwal (H-1).
        </div>

        <div>
          <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-1.5 block">
            Pilih Tanggal Baru:
          </label>
          <input
            v-model="rescheduleDate"
            type="date"
            class="w-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl px-3 py-2 text-xs outline-none"
            @change="loadRescheduleSlots"
          />
        </div>

        <div>
          <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-1.5 block">
            Pilih Slot Waktu Baru:
          </label>
          <div v-if="loadingRescheduleSlots" class="py-4 text-center text-xs text-neutral-400">
            Memuat slot...
          </div>
          <div v-else-if="rescheduleSlots.length === 0" class="p-3 bg-amber-50 text-amber-700 text-xs rounded-xl text-center">
            Tidak ada slot tersedia di hari ini.
          </div>
          <div v-else class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto p-1">
            <button
              v-for="slot in rescheduleSlots"
              :key="slot.start_time"
              type="button"
              class="p-2 rounded-xl border text-xs text-center"
              :class="selectedNewSlot?.start_time === slot.start_time
                ? 'border-rose-500 bg-rose-50 text-rose-600 font-bold'
                : 'border-neutral-200 hover:bg-neutral-50'"
              @click="selectedNewSlot = slot"
            >
              {{ slot.start_time }} - {{ slot.end_time }}
            </button>
          </div>
        </div>

        <div>
          <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-1.5 block">
            Alasan Reschedule:
          </label>
          <input
            v-model="rescheduleReason"
            type="text"
            placeholder="Contoh: Ada keperluan mendesak"
            class="w-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl px-3 py-2 text-xs outline-none"
          />
        </div>

        <button
          type="button"
          class="w-full py-3 rounded-xl bg-rose-500 text-white text-xs font-bold hover:bg-rose-600 transition-colors shadow-xs"
          :disabled="isSubmittingReschedule || !selectedNewSlot"
          @click="submitReschedule"
        >
          {{ isSubmittingReschedule ? 'Menyimpan...' : 'Konfirmasi Reschedule' }}
        </button>
      </div>
    </div>

    <!-- ================= CANCEL MODAL ================= -->
    <div
      v-if="cancelModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
    >
      <div class="bg-white dark:bg-neutral-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-neutral-200 dark:border-neutral-800 space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
          <h3 class="font-display text-base font-bold text-neutral-900 dark:text-neutral-100">
            Batalkan Jadwal Konsultasi
          </h3>
          <button type="button" class="p-1 text-neutral-400 hover:text-neutral-600" @click="cancelModalOpen = false">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <div v-if="cancelError" class="p-3 rounded-xl bg-rose-50 text-rose-700 text-xs">
          {{ cancelError }}
        </div>

        <div class="p-4 rounded-2xl bg-rose-50/70 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900 text-xs text-neutral-700 dark:text-neutral-300 space-y-2">
          <p class="font-bold text-rose-600">Kebijakan Pengembalian Dana (Refund Policy):</p>
          <ul class="list-disc list-inside space-y-1 text-[11px] text-neutral-600 dark:text-neutral-400">
            <li><strong>H-3 atau lebih (&gt;72 jam):</strong> Refund 100%</li>
            <li><strong>H-2 (48 - 72 jam):</strong> Refund 75%</li>
            <li><strong>H-1 (24 - 48 jam):</strong> Refund 50%</li>
            <li><strong>Kurang dari 24 jam:</strong> 0% (tidak dapat direfund)</li>
          </ul>
        </div>

        <div>
          <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-1.5 block">
            Alasan Pembatalan:
          </label>
          <textarea
            v-model="cancelReason"
            rows="3"
            placeholder="Tuliskan alasan pembatalan konsultasi..."
            class="w-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl p-3 text-xs outline-none"
          />
        </div>

        <div class="flex items-center gap-2 pt-2">
          <button
            type="button"
            class="flex-1 py-2.5 rounded-xl border border-neutral-200 text-neutral-700 text-xs font-semibold hover:bg-neutral-50"
            @click="cancelModalOpen = false"
          >
            Batal
          </button>

          <button
            type="button"
            class="flex-1 py-2.5 rounded-xl bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 transition-colors shadow-xs"
            :disabled="isSubmittingCancel"
            @click="submitCancel"
          >
            {{ isSubmittingCancel ? 'Membatalkan...' : 'Ya, Batalkan Sesi' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
