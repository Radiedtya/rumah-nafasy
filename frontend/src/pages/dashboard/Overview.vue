<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import {
  CalendarDaysIcon,
  ClockIcon,
  StarIcon,
  VideoCameraIcon,
  BanknotesIcon,
  CheckCircleIcon,
  ArrowTopRightOnSquareIcon,
  ArrowRightIcon,
  PlusIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '../../stores/auth'
import { apiFetch } from '../../lib/api'
import PageHeader from '../../components/dashboard/PageHeader.vue'
import StatCard from '../../components/dashboard/StatCard.vue'
import StatusPill from '../../components/dashboard/StatusPill.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseModal from '../../components/ui/BaseModal.vue'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseAvatar from '../../components/ui/BaseAvatar.vue'
import BaseSkeleton from '../../components/ui/BaseSkeleton.vue'
import BaseEmpty from '../../components/ui/BaseEmpty.vue'

const auth = useAuthStore()

const loading = ref(true)
const pasienStats = ref({
  totalBookings: 0,
  upcomingBookings: 0,
  completedBookings: 0,
})
const upcomingSessions = ref<any[]>([])
const allBookings = ref<any[]>([])

const psikologData = ref<{
  today_bookings: any[]
  today_bookings_count: number
  upcoming_bookings_count: number
  total_completed_consultations: number
  monthly_income: number
  total_income: number
  rating_avg: number
  total_reviews: number
  is_available: boolean
  specialization?: string
} | null>(null)

async function loadData() {
  loading.value = true
  try {
    if (auth.isPsikolog) {
      const res = await apiFetch('psikolog/dashboard')
      psikologData.value = res.data
    } else {
      // Pasien data
      const bookingsRes = await apiFetch('pasien/bookings').catch(() => ({ data: [] }))

      const bookings = bookingsRes.data?.data || bookingsRes.data || []
      allBookings.value = bookings

      pasienStats.value = {
        totalBookings: bookings.length,
        upcomingBookings: bookings.filter((b: any) =>
          ['pending_psikolog', 'confirmed', 'in_progress'].includes(b.status),
        ).length,
        completedBookings: bookings.filter((b: any) => b.status === 'completed').length,
      }

      upcomingSessions.value = bookings
        .filter((b: any) => ['pending_psikolog', 'confirmed', 'in_progress'].includes(b.status))
        .slice(0, 3)
    }
  } catch (err) {
    console.error('Failed loading dashboard overview', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})

watch(
  () => auth.user?.id,
  () => {
    loadData()
  },
)

function formatRupiah(amount: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(amount)
}

function formatDate(d: string) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('id-ID', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
  })
}

// ── Persetujuan pengajuan booking (psikolog) ───────────────────────────────
const decidingId = ref<number | null>(null)
const rejectOpen = ref(false)
const rejectTarget = ref<any>(null)
const rejectReason = ref('')

async function decideRequest(booking: any, decision: 'confirmed' | 'rejected') {
  if (decision === 'rejected') {
    rejectTarget.value = booking
    rejectReason.value = ''
    rejectOpen.value = true
    return
  }
  decidingId.value = booking.id
  try {
    await apiFetch(`psikolog/bookings/${booking.id}/status`, {
      method: 'PUT',
      body: JSON.stringify({ status: 'confirmed' }),
    })
    await loadData()
  } catch (e: any) {
    console.error('Gagal menyetujui pengajuan', e)
  } finally {
    decidingId.value = null
  }
}

async function submitReject() {
  if (!rejectTarget.value) return
  decidingId.value = rejectTarget.value.id
  try {
    await apiFetch(`psikolog/bookings/${rejectTarget.value.id}/status`, {
      method: 'PUT',
      body: JSON.stringify({
        status: 'rejected',
        rejected_reason: rejectReason.value || 'Jadwal tidak dapat disetujui',
      }),
    })
    rejectOpen.value = false
    rejectTarget.value = null
    await loadData()
  } catch (e: any) {
    console.error('Gagal menolak pengajuan', e)
  } finally {
    decidingId.value = null
  }
}
</script>

<template>
  <div>
    <PageHeader
      :title="`Halo, ${auth.user?.name?.split(' ')[0] || 'Kawan'}`"
      :description="
        auth.isPsikolog
          ? 'Ringkasan praktik Anda hari ini — jadwal, pasien, dan pendapatan.'
          : 'Ringkasan aktivitas konsultasi dan transaksi Anda.'
      "
    >
      <template #actions>
        <RouterLink v-if="!auth.isPsikolog" to="/dashboard/psikolog">
          <BaseButton size="sm">
            <PlusIcon class="h-3.5 w-3.5" />
            Booking Sesi
          </BaseButton>
        </RouterLink>
        <RouterLink v-else to="/dashboard/jadwal">
          <BaseButton size="sm">
            <CalendarDaysIcon class="h-3.5 w-3.5" />
            Kelola Jadwal
          </BaseButton>
        </RouterLink>
      </template>
    </PageHeader>

    <!-- ================= PASIEN OVERVIEW ================= -->
    <div v-if="!auth.isPsikolog" class="space-y-5">
      <!-- Stats -->
      <div v-if="loading" class="grid grid-cols-2 gap-4 lg:grid-cols-3">
        <BaseSkeleton v-for="i in 3" :key="i" class="h-24" />
      </div>
      <div v-else class="grid grid-cols-2 gap-4 lg:grid-cols-3">
        <StatCard
          label="Total Sesi"
          :value="pasienStats.totalBookings"
          sub="Seluruh booking dibuat"
          :icon="CalendarDaysIcon"
        />
        <StatCard
          label="Sesi Mendatang"
          :value="pasienStats.upcomingBookings"
          sub="Termasuk menunggu persetujuan"
          :icon="ClockIcon"
          tone="warning"
        />
        <StatCard
          label="Selesai"
          :value="pasienStats.completedBookings"
          sub="Konsultasi tuntas"
          :icon="CheckCircleIcon"
          tone="success"
        />
      </div>

      <div class="grid gap-5 lg:grid-cols-2">
        <!-- Sesi Mendatang -->
        <BaseCard class="flex flex-col">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-[var(--text)]">Sesi Mendatang</h2>
            <RouterLink
              to="/dashboard/sesi"
              class="inline-flex items-center gap-1 text-xs font-medium text-[var(--accent)] transition-opacity hover:opacity-80"
            >
              Lihat semua
              <ArrowRightIcon class="h-3 w-3" />
            </RouterLink>
          </div>

          <BaseEmpty
            v-if="!loading && upcomingSessions.length === 0"
            icon="🗓"
            title="Belum ada sesi aktif"
            description="Pilih psikolog favorit Anda dan jadwalkan sesi pertama."
          >
            <RouterLink to="/dashboard/psikolog">
              <BaseButton size="sm" variant="secondary">Cari Psikolog</BaseButton>
            </RouterLink>
          </BaseEmpty>

          <div v-else-if="loading" class="space-y-2.5">
            <BaseSkeleton v-for="i in 2" :key="i" class="h-16" />
          </div>

          <div v-else class="space-y-2.5">
            <div
              v-for="sesi in upcomingSessions"
              :key="sesi.id"
              class="rounded-xl border border-[var(--line)] p-3.5 transition-colors hover:bg-[var(--muted)]/4"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                  <BaseAvatar :name="sesi.psikolog?.name || 'P'" size="sm" />
                  <div class="min-w-0">
                    <p class="truncate text-xs font-semibold text-[var(--text)]">
                      {{ sesi.psikolog?.name }}
                    </p>
                    <p class="truncate text-[11px] text-[var(--muted)]">
                      {{ sesi.psikolog?.specialization || 'Psikolog Terverifikasi' }}
                    </p>
                  </div>
                </div>
                <StatusPill :status="sesi.status" />
              </div>

              <div
                class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-[var(--line)] pt-2.5 text-[11px] text-[var(--muted)]"
              >
                <span class="font-medium text-[var(--text)]">{{ formatDate(sesi.booking_date) }}</span>
                <span class="tabular-nums">{{ sesi.start_time }}–{{ sesi.end_time }} WIB</span>
              </div>

              <a
                v-if="sesi.room_id"
                :href="`https://meet.jit.si/${sesi.room_id}`"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-2.5 inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-emerald-600 py-2 text-[11px] font-semibold text-white transition-colors hover:bg-emerald-700"
              >
                <VideoCameraIcon class="h-3.5 w-3.5" />
                Masuk Ruang Konsultasi
                <ArrowTopRightOnSquareIcon class="h-3 w-3" />
              </a>
            </div>
          </div>
        </BaseCard>

        <!-- Riwayat Booking -->
        <BaseCard class="flex flex-col">
          <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-[var(--text)]">Booking Terbaru</h2>
          </div>

          <BaseEmpty
            v-if="!loading && allBookings.length === 0"
            icon="🗓"
            title="Belum ada booking"
            description="Ajukan konsultasi pertama Anda — tanpa pembayaran di aplikasi."
          />

          <div v-else-if="loading" class="space-y-2.5">
            <BaseSkeleton v-for="i in 3" :key="i" class="h-12" />
          </div>

          <div v-else class="space-y-2">
            <div
              v-for="b in allBookings.slice(0, 5)"
              :key="b.id"
              class="flex items-center justify-between gap-3 rounded-xl border border-[var(--line)] px-3.5 py-3 transition-colors hover:bg-[var(--muted)]/4"
            >
              <div class="min-w-0">
                <p class="truncate text-xs font-medium text-[var(--text)]">
                  {{ b.psikolog?.name || 'Psikolog' }}
                </p>
                <p class="mt-0.5 truncate text-[11px] text-[var(--muted)]">
                  {{ formatDate(b.booking_date) }} · {{ b.start_time }}–{{ b.end_time }} · {{ b.duration_minutes ?? 60 }}m
                </p>
              </div>
              <div class="shrink-0">
                <StatusPill :status="b.status" />
              </div>
            </div>
          </div>
        </BaseCard>
      </div>
    </div>

    <!-- ================= PSIKOLOG OVERVIEW ================= -->
    <div v-else class="space-y-5">
      <!-- Stats -->
      <div v-if="loading" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <BaseSkeleton v-for="i in 4" :key="i" class="h-24" />
      </div>
      <div v-else class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatCard
          label="Pendapatan Bulan Ini"
          :value="formatRupiah(psikologData?.monthly_income || 0)"
          :icon="BanknotesIcon"
          tone="success"
        />
        <StatCard
          label="Sesi Hari Ini"
          :value="psikologData?.today_bookings_count ?? 0"
          :icon="CalendarDaysIcon"
          tone="accent"
        />
        <StatCard
          label="Sesi Mendatang"
          :value="psikologData?.upcoming_bookings_count ?? 0"
          :icon="ClockIcon"
          tone="warning"
        />
        <StatCard
          label="Rating"
          :value="psikologData?.rating_avg ? psikologData.rating_avg.toFixed(1) : '5.0'"
          :sub="`${psikologData?.total_reviews || 0} ulasan`"
          :icon="StarIcon"
        />
      </div>

      <!-- Antrean hari ini -->
      <BaseCard>
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h2 class="text-sm font-semibold text-[var(--text)]">Antrean Pasien Hari Ini</h2>
            <p class="mt-0.5 text-[11px] text-[var(--muted)]">
              Sesi konsultasi yang dijadwalkan hari ini
            </p>
          </div>
          <RouterLink
            to="/dashboard/konsultasi"
            class="inline-flex items-center gap-1 text-xs font-medium text-[var(--accent)] transition-opacity hover:opacity-80"
          >
            Ruang Konsultasi
            <ArrowRightIcon class="h-3 w-3" />
          </RouterLink>
        </div>

        <BaseEmpty
          v-if="!loading && (!psikologData?.today_bookings || psikologData.today_bookings.length === 0)"
          icon="☕"
          title="Tidak ada jadwal sesi hari ini"
          description="Nikmati waktu luang Anda atau periksa jadwal praktek untuk pekan ini."
        />

        <div v-else-if="loading" class="space-y-2.5">
          <BaseSkeleton v-for="i in 2" :key="i" class="h-16" />
        </div>

        <div v-else class="space-y-2.5">
          <div
            v-for="booking in psikologData?.today_bookings || []"
            :key="booking.id"
            class="flex flex-col justify-between gap-3 rounded-xl border border-[var(--line)] p-3.5 transition-colors hover:bg-[var(--muted)]/4 md:flex-row md:items-center"
          >
            <div class="flex min-w-0 items-center gap-3">
              <BaseAvatar :name="booking.pasien?.name || 'P'" size="sm" />
              <div class="min-w-0">
                <p class="truncate text-xs font-semibold text-[var(--text)]">
                  {{ booking.pasien?.name }}
                </p>
                <p class="text-[11px] tabular-nums text-[var(--muted)]">
                  {{ booking.start_time }}–{{ booking.end_time }} WIB
                </p>
              </div>
            </div>

            <!-- Persetujuan pengajuan (alur tanpa pembayaran) -->
            <div
              v-if="booking.status === 'pending_psikolog'"
              class="flex shrink-0 items-center gap-2"
            >
              <BaseButton
                size="sm"
                :disabled="decidingId === booking.id"
                @click="decideRequest(booking, 'confirmed')"
              >
                Setujui
              </BaseButton>
              <BaseButton
                size="sm"
                variant="ghost"
                class="!text-rose-600 dark:!text-rose-400 hover:!bg-rose-500/10"
                :disabled="decidingId === booking.id"
                @click="decideRequest(booking, 'rejected')"
              >
                Tolak
              </BaseButton>
            </div>

            <a
              v-else-if="booking.room_id"
              :href="`https://meet.jit.si/${booking.room_id}`"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-[11px] font-semibold text-white transition-colors hover:bg-emerald-700"
            >
              <VideoCameraIcon class="h-3.5 w-3.5" />
              Mulai / Masuk Sesi
            </a>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- ================= MODAL TOLAK PENGAJUAN ================= -->
    <BaseModal
      v-model:open="rejectOpen"
      title="Tolak Pengajuan Konsultasi"
      max-width="max-w-md"
    >
      <div class="space-y-4">
        <p class="text-xs leading-relaxed text-[var(--muted)]">
          Pengajuan dari <strong class="text-[var(--text)]">{{ rejectTarget?.pasien?.name }}</strong>
          akan ditolak dan pasien diberi tahu. Slot akan dilepas.
        </p>
        <div>
          <label class="field-label">Alasan Penolakan</label>
          <textarea
            v-model="rejectReason"
            rows="3"
            maxlength="500"
            placeholder="Contoh: jadwal bentrok, kuota penuh…"
            class="field-input resize-none"
          />
        </div>
        <div class="flex items-center gap-2">
          <BaseButton variant="secondary" size="md" class="flex-1" @click="rejectOpen = false">
            Kembali
          </BaseButton>
          <BaseButton variant="danger" size="md" class="flex-1" :disabled="decidingId !== null" @click="submitReject">
            {{ decidingId !== null ? 'Memproses…' : 'Tolak Pengajuan' }}
          </BaseButton>
        </div>
      </div>
    </BaseModal>
  </div>
</template>
