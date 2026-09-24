<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { CalendarDaysIcon, ClockIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import { apiFetch } from '../../lib/api'
import PageHeader from '../../components/dashboard/PageHeader.vue'
import StatusPill from '../../components/dashboard/StatusPill.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseAvatar from '../../components/ui/BaseAvatar.vue'
import BaseSkeleton from '../../components/ui/BaseSkeleton.vue'
import BaseEmpty from '../../components/ui/BaseEmpty.vue'
import BaseTabs from '../../components/ui/BaseTabs.vue'

/**
 * Daftar sesi — TANPA tombol aksi. Semua aksi (video call, reschedule,
 * batal) pindah ke halaman detail: /dashboard/sesi/:id
 */

const loading = ref(true)
const bookings = ref<any[]>([])
const activeTab = ref('today')

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

let pollingTimer: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  fetchBookings()
  // Polling realtime setiap 30 detik
  pollingTimer = setInterval(() => fetchBookings(), 30_000)
})

onUnmounted(() => {
  if (pollingTimer) clearInterval(pollingTimer)
})

function isToday(dateStr: string): boolean {
  if (!dateStr) return false
  const today = new Date()
  const d = new Date(dateStr)
  return (
    d.getFullYear() === today.getFullYear() &&
    d.getMonth() === today.getMonth() &&
    d.getDate() === today.getDate()
  )
}

const tabCounts = computed(() => ({
  today: bookings.value.filter(
    (b) => isToday(b.booking_date) && ['confirmed', 'in_progress', 'pending_psikolog'].includes(b.status),
  ).length,
  upcoming: bookings.value.filter(
    (b) => !isToday(b.booking_date) && ['pending_psikolog', 'confirmed', 'in_progress'].includes(b.status),
  ).length,
  completed: bookings.value.filter((b) => b.status === 'completed').length,
  cancelled: bookings.value.filter((b) => b.status === 'cancelled' || b.status === 'rejected').length,
  all: bookings.value.length,
}))

const tabs = computed(() => [
  { value: 'today', label: 'Sesi Hari Ini', count: tabCounts.value.today },
  { value: 'upcoming', label: 'Mendatang', count: tabCounts.value.upcoming },
  { value: 'completed', label: 'Selesai', count: tabCounts.value.completed },
  { value: 'cancelled', label: 'Dibatalkan', count: tabCounts.value.cancelled },
  { value: 'all', label: 'Semua', count: tabCounts.value.all },
])

const filteredBookings = computed(() => {
  if (activeTab.value === 'today') {
    return bookings.value.filter(
      (b) => isToday(b.booking_date) && ['confirmed', 'in_progress', 'pending_psikolog'].includes(b.status),
    )
  }
  if (activeTab.value === 'upcoming') {
    return bookings.value.filter(
      (b) => !isToday(b.booking_date) && ['pending_psikolog', 'confirmed', 'in_progress'].includes(b.status),
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

function formatDate(d: string) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('id-ID', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function complaintPreview(md: string | null | undefined) {
  if (!md) return ''
  return md
    .replace(/[#*`>\-_]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .slice(0, 90)
}
</script>

<template>
  <div>
    <PageHeader
      title="Sesi Saya"
      description="Daftar pengajuan & jadwal konsultasi Anda. Klik sesi untuk melihat progres dan detailnya."
    >
      <template #actions>
        <RouterLink to="/dashboard/psikolog">
          <BaseButton size="sm">Booking Sesi Baru</BaseButton>
        </RouterLink>
      </template>
    </PageHeader>

    <!-- Tabs -->
    <BaseTabs v-model="activeTab" :tabs="tabs" class="mb-5" />

    <!-- Bookings List -->
    <div v-if="loading" class="space-y-3">
      <BaseSkeleton v-for="i in 3" :key="i" class="h-28" />
    </div>

    <BaseCard v-else-if="filteredBookings.length === 0" :padded="false">
      <BaseEmpty
        icon="🗓"
        title="Tidak ada data sesi pada tab ini"
        description="Sesi yang Anda jadwalkan akan muncul di sini."
      />
    </BaseCard>

    <div v-else class="space-y-3">
      <RouterLink
        v-for="booking in filteredBookings"
        :key="booking.id"
        :to="`/dashboard/sesi/${booking.id}`"
        class="block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent)]/50"
      >
        <BaseCard
          class="cursor-pointer transition-colors hover:border-[var(--accent)]/40 hover:bg-[var(--muted)]/4"
        >
          <div class="flex items-center justify-between gap-4">
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

              <!-- Psikolog -->
              <div class="flex items-center gap-3">
                <BaseAvatar :name="booking.psikolog?.name || 'P'" size="md" />
                <div class="min-w-0">
                  <h3 class="truncate text-sm font-semibold text-[var(--text)]">
                    {{ booking.psikolog?.name }}
                  </h3>
                  <p class="truncate text-[11px] text-[var(--muted)]">
                    {{ booking.psikolog?.specialization || 'Psikolog Terverifikasi' }}
                    · {{ booking.consultation_type === 'offline' ? 'Offline' : 'Video' }} ·
                    {{ booking.duration_minutes ?? 60 }} menit
                  </p>
                </div>
              </div>

              <!-- Time -->
              <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-[var(--muted)]">
                <span class="inline-flex items-center gap-1.5 font-medium text-[var(--text)]">
                  <CalendarDaysIcon class="h-3.5 w-3.5" />
                  {{ formatDate(booking.booking_date) }}
                </span>
                <span class="inline-flex items-center gap-1.5 tabular-nums">
                  <ClockIcon class="h-3.5 w-3.5" />
                  {{ booking.start_time }}–{{ booking.end_time }} WIB
                </span>
              </div>

              <!-- Preview keluhan -->
              <p
                v-if="complaintPreview(booking.complaint_markdown)"
                class="truncate text-[11px] italic text-[var(--muted)]"
              >
                "{{ complaintPreview(booking.complaint_markdown) }}…"
              </p>
            </div>

            <ChevronRightIcon class="h-5 w-5 shrink-0 text-[var(--muted)]" />
          </div>
        </BaseCard>
      </RouterLink>
    </div>
  </div>
</template>
