<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import {
  CalendarDaysIcon,
  ClockIcon,
  VideoCameraIcon,
  ArrowPathIcon,
  XCircleIcon,
  ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline'
import { apiFetch } from '../../lib/api'
import PageHeader from '../../components/dashboard/PageHeader.vue'
import StatusPill from '../../components/dashboard/StatusPill.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseAvatar from '../../components/ui/BaseAvatar.vue'
import BaseSkeleton from '../../components/ui/BaseSkeleton.vue'
import BaseEmpty from '../../components/ui/BaseEmpty.vue'
import BaseModal from '../../components/ui/BaseModal.vue'
import BaseTabs from '../../components/ui/BaseTabs.vue'

const loading = ref(true)
const bookings = ref<any[]>([])
const activeTab = ref('upcoming')

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

const tabCounts = computed(() => ({
  upcoming: bookings.value.filter((b) =>
    ['pending_psikolog', 'confirmed', 'in_progress'].includes(b.status),
  ).length,
  completed: bookings.value.filter((b) => b.status === 'completed').length,
  cancelled: bookings.value.filter((b) => b.status === 'cancelled' || b.status === 'rejected').length,
  all: bookings.value.length,
}))

const tabs = computed(() => [
  { value: 'upcoming', label: 'Mendatang', count: tabCounts.value.upcoming },
  { value: 'completed', label: 'Selesai', count: tabCounts.value.completed },
  { value: 'cancelled', label: 'Dibatalkan', count: tabCounts.value.cancelled },
  { value: 'all', label: 'Semua', count: tabCounts.value.all },
])

const filteredBookings = computed(() => {
  if (activeTab.value === 'upcoming') {
    return bookings.value.filter((b) =>
      ['pending_psikolog', 'confirmed', 'in_progress'].includes(b.status),
    )
  }
  if (activeTab.value === 'completed') {
    return bookings.value.filter((b) => b.status === 'completed')
  }
  if (activeTab.value === 'cancelled') {
    return bookings.value.filter((b) => b.status === 'cancelled' || b.status === 'rejected')
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
    const durMinutes = selectedBooking.value.duration_minutes || selectedBooking.value.order?.duration_minutes || 60
    const res = await apiFetch(
      `pasien/psikolog/${selectedBooking.value.psikolog.id}/slots?date=${rescheduleDate.value}&duration_minutes=${durMinutes}`,
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

function formatDate(d: string) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('id-ID', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}
</script>

<template>
  <div>
    <PageHeader
      title="Sesi Saya"
      description="Kelola jadwal konsultasi aktif, akses video call, atau lakukan reschedule sesuai kebijakan resmi."
    >
      <template #actions>
        <RouterLink to="/dashboard/psikolog">
          <BaseButton size="sm">
            <VideoCameraIcon class="h-3.5 w-3.5" />
            Booking Sesi Baru
          </BaseButton>
        </RouterLink>
      </template>
    </PageHeader>

    <!-- Tabs -->
    <BaseTabs v-model="activeTab" :tabs="tabs" class="mb-5" />

    <!-- Bookings List -->
    <div v-if="loading" class="space-y-3">
      <BaseSkeleton v-for="i in 3" :key="i" class="h-32" />
    </div>

    <BaseCard v-else-if="filteredBookings.length === 0" :padded="false">
      <BaseEmpty
        icon="🗓"
        title="Tidak ada data sesi pada tab ini"
        description="Sesi yang Anda jadwalkan akan muncul di sini."
      />
    </BaseCard>

    <div v-else class="space-y-3">
      <BaseCard
        v-for="booking in filteredBookings"
        :key="booking.id"
        class="flex flex-col justify-between gap-5 md:flex-row md:items-center"
      >
        <div class="min-w-0 space-y-3">
          <!-- Status -->
          <div class="flex flex-wrap items-center gap-2">
            <StatusPill :status="booking.status" />
            <span
              v-if="booking.reschedule_count > 0"
              class="rounded bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-amber-600 dark:text-amber-400"
            >
              Reschedule: {{ booking.reschedule_count }}/2x
            </span>
          </div>
          <p
            v-if="booking.status === 'rejected' && booking.rejected_reason"
            class="text-[11px] text-rose-600 dark:text-rose-400"
          >
            Alasan penolakan: {{ booking.rejected_reason }}
          </p>

          <!-- Psikolog -->
          <div class="flex items-center gap-3">
            <BaseAvatar :name="booking.psikolog?.name || 'P'" size="md" />
            <div class="min-w-0">
              <h3 class="truncate text-sm font-semibold text-[var(--text)]">
                {{ booking.psikolog?.name }}
              </h3>
              <p class="truncate text-[11px] text-[var(--muted)]">
                {{ booking.psikolog?.specialization || 'Psikolog Terverifikasi' }}
              </p>
            </div>
          </div>

          <!-- Time & details -->
          <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-[var(--muted)]">
            <span class="inline-flex items-center gap-1.5 font-medium text-[var(--text)]">
              <CalendarDaysIcon class="h-3.5 w-3.5" />
              {{ formatDate(booking.booking_date) }}
            </span>
            <span class="inline-flex items-center gap-1.5 tabular-nums">
              <ClockIcon class="h-3.5 w-3.5" />
              {{ booking.start_time }}–{{ booking.end_time }} WIB
            </span>
            <span>({{ booking.duration_minutes ?? 60 }} menit · {{ booking.consultation_type === 'offline' ? 'Offline' : 'Video' }})</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex shrink-0 flex-col items-stretch gap-2 sm:flex-row sm:items-center">
          <a
            v-if="booking.room_id && (booking.status === 'confirmed' || booking.status === 'in_progress')"
            :href="`https://meet.jit.si/${booking.room_id}`"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white transition-colors hover:bg-emerald-700"
          >
            <VideoCameraIcon class="h-3.5 w-3.5" />
            Masuk Video Call
            <ArrowTopRightOnSquareIcon class="h-3 w-3" />
          </a>

          <BaseButton
            v-if="booking.status === 'confirmed' && booking.can_reschedule"
            variant="secondary"
            size="sm"
            @click="openReschedule(booking)"
          >
            <ArrowPathIcon class="h-3.5 w-3.5 text-[var(--muted)]" />
            Reschedule
          </BaseButton>

          <BaseButton
            v-if="booking.status === 'confirmed' && booking.can_cancel"
            variant="ghost"
            size="sm"
            class="!text-rose-600 dark:!text-rose-400 hover:!bg-rose-500/10"
            @click="openCancel(booking)"
          >
            <XCircleIcon class="h-3.5 w-3.5" />
            Batalkan
          </BaseButton>
        </div>
      </BaseCard>
    </div>

    <!-- ================= RESCHEDULE MODAL ================= -->
    <BaseModal
      v-model:open="rescheduleModalOpen"
      title="Reschedule Jadwal Konsultasi"
      description="Maksimal 2x dan minimal 24 jam sebelum jadwal (H-1)."
      max-width="max-w-md"
    >
      <div class="space-y-4">
        <div v-if="rescheduleError" class="rounded-lg bg-rose-500/10 px-3 py-2 text-xs text-rose-600 dark:text-rose-400">
          {{ rescheduleError }}
        </div>

        <div>
          <label class="field-label">Pilih Tanggal Baru</label>
          <input
            v-model="rescheduleDate"
            type="date"
            class="field-input"
            @change="loadRescheduleSlots"
          />
        </div>

        <div>
          <label class="field-label">Pilih Slot Waktu Baru</label>
          <div v-if="loadingRescheduleSlots" class="py-4 text-center text-xs text-[var(--muted)]">
            Memuat slot…
          </div>
          <div
            v-else-if="rescheduleSlots.length === 0"
            class="rounded-lg bg-amber-500/10 px-3 py-2.5 text-center text-xs text-amber-600 dark:text-amber-400"
          >
            Tidak ada slot tersedia di tanggal ini.
          </div>
          <div v-else class="grid max-h-40 grid-cols-2 gap-2 overflow-y-auto">
            <button
              v-for="slot in rescheduleSlots"
              :key="slot.start_time"
              type="button"
              class="rounded-lg border px-2 py-2 text-center text-xs tabular-nums transition-all"
              :class="
                selectedNewSlot?.start_time === slot.start_time
                  ? 'border-[var(--accent)] bg-[var(--accent)]/10 font-semibold text-[var(--accent)]'
                  : 'border-[var(--line)] text-[var(--muted)] hover:bg-[var(--muted)]/8'
              "
              @click="selectedNewSlot = slot"
            >
              {{ slot.start_time }}–{{ slot.end_time }}
            </button>
          </div>
        </div>

        <div>
          <label class="field-label">Alasan Reschedule</label>
          <input v-model="rescheduleReason" type="text" placeholder="Contoh: Ada keperluan mendesak" class="field-input" />
        </div>

        <BaseButton
          size="md"
          class="w-full"
          :disabled="isSubmittingReschedule || !selectedNewSlot"
          @click="submitReschedule"
        >
          {{ isSubmittingReschedule ? 'Menyimpan…' : 'Konfirmasi Reschedule' }}
        </BaseButton>
      </div>
    </BaseModal>

    <!-- ================= CANCEL MODAL ================= -->
    <BaseModal
      v-model:open="cancelModalOpen"
      title="Batalkan Jadwal Konsultasi"
      max-width="max-w-md"
    >
      <div class="space-y-4">
        <div v-if="cancelError" class="rounded-lg bg-rose-500/10 px-3 py-2 text-xs text-rose-600 dark:text-rose-400">
          {{ cancelError }}
        </div>

        <!-- Kebijakan refund hanya untuk booking alur lama (via order terbayar) -->
        <div v-if="cancelBookingData?.order" class="rounded-xl border border-[var(--line)] bg-[var(--muted)]/5 p-4">
          <p class="text-xs font-semibold text-[var(--text)]">Kebijakan Pengembalian Dana</p>
          <ul class="mt-2 list-inside list-disc space-y-1 text-[11px] leading-relaxed text-[var(--muted)]">
            <li><strong class="text-[var(--text)]">H-3 atau lebih:</strong> Refund 100%</li>
            <li><strong class="text-[var(--text)]">H-2 (48–72 jam):</strong> Refund 75%</li>
            <li><strong class="text-[var(--text)]">H-1 (24–48 jam):</strong> Refund 50%</li>
            <li><strong class="text-[var(--text)]">Kurang dari 24 jam:</strong> Tidak dapat direfund</li>
          </ul>
        </div>
        <div v-else class="rounded-xl bg-[var(--muted)]/6 p-3.5">
          <p class="text-[11px] leading-relaxed text-[var(--muted)]">
            Pengajuan ini dibatalkan tanpa biaya — tidak ada pembayaran yang diproses aplikasi.
          </p>
        </div>

        <div>
          <label class="field-label">Alasan Pembatalan</label>
          <textarea
            v-model="cancelReason"
            rows="3"
            placeholder="Tuliskan alasan pembatalan konsultasi…"
            class="field-input resize-none"
          />
        </div>

        <div class="flex items-center gap-2">
          <BaseButton variant="secondary" size="md" class="flex-1" @click="cancelModalOpen = false">
            Kembali
          </BaseButton>
          <BaseButton variant="danger" size="md" class="flex-1" :disabled="isSubmittingCancel" @click="submitCancel">
            {{ isSubmittingCancel ? 'Membatalkan…' : 'Ya, Batalkan Sesi' }}
          </BaseButton>
        </div>
      </div>
    </BaseModal>
  </div>
</template>
