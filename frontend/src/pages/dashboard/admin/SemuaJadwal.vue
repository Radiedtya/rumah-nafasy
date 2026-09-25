<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { apiFetch } from '../../../lib/api'
import PageHeader from '../../../components/dashboard/PageHeader.vue'
import BaseCard from '../../../components/ui/BaseCard.vue'
import BaseBadge from '../../../components/ui/BaseBadge.vue'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

interface Person { id: number; name: string; avatar: string | null; specialization?: string }
interface BookingOrder {
  id: number; order_number: string; calculated_price: number
  category_name: string; duration_name: string; duration_minutes: number
  consultation_type: string; status: string
}
interface Booking {
  id: number
  order: BookingOrder | null
  pasien: Person | null
  psikolog: Person | null
  booking_date: string; start_time: string; end_time: string
  status: string; room_id: string | null
  consultation_type: string; duration_minutes: number | null
  requested_category: { id: number; name: string; base_price: number } | null
  estimated_price: number | null
  consultation: { id: number; status: string } | null
  created_at: string
}
interface Meta { current_page: number; last_page: number; total: number }

const list = ref<Booking[]>([])
const meta = ref<Meta>({ current_page: 1, last_page: 1, total: 0 })
const isLoading = ref(false)
const search = ref('')
const filterStatus = ref('')
const filterDate = ref('')

const STATUS_COLORS: Record<string, 'success' | 'warning' | 'danger' | 'info' | 'neutral'> = {
  confirmed: 'success', pending: 'warning', cancelled: 'danger',
  completed: 'info', in_progress: 'accent' as any, no_show: 'neutral',
}
const STATUS_LABELS: Record<string, string> = {
  confirmed: 'Terkonfirmasi', pending: 'Menunggu', cancelled: 'Dibatalkan',
  completed: 'Selesai', in_progress: 'Berlangsung', no_show: 'Tidak Hadir',
}

function fmtDate(d: string) {
  return new Date(d).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })
}
function fmtCurrency(n: number) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n)
}

let currentPage = 1
async function fetchList(page = 1) {
  currentPage = page
  isLoading.value = true
  try {
    const params = new URLSearchParams({ page: String(page), per_page: '15' })
    if (search.value) params.set('search', search.value)
    if (filterStatus.value) params.set('status', filterStatus.value)
    if (filterDate.value) params.set('date', filterDate.value)
    const res = await apiFetch(`admin/bookings?${params}`)
    // paginateResponse returns: { success, message, data: [...], meta: {...} } at root
    list.value = res.data ?? []
    meta.value = res.meta ?? { current_page: 1, last_page: 1, total: 0 }
  } catch { } finally { isLoading.value = false }
}

let pollingTimer: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  fetchList()
  // Polling realtime setiap 30 detik
  pollingTimer = setInterval(() => fetchList(currentPage), 30_000)
})

onUnmounted(() => {
  if (pollingTimer) clearInterval(pollingTimer)
})

function initials(name: string) {
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}
function consultationLabel(type: string) {
  return type === 'offline' ? 'Offline' : 'Video Call'
}
</script>

<template>
  <div class="mx-auto max-w-6xl">
    <PageHeader
      title="Semua Jadwal"
      description="Pantau seluruh jadwal booking dari semua psikolog."
    />

    <!-- Filters -->
    <div class="mb-4 flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-[180px]">
        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[var(--muted)]" />
        <input
          v-model="search" type="search" placeholder="Cari nama pasien / psikolog..."
          class="w-full h-9 pl-9 pr-3 rounded-lg border border-[var(--line)] bg-[var(--surface)] text-sm text-[var(--text)] outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)]/20"
          @input="fetchList(1)"
        />
      </div>
      <input
        v-model="filterDate" type="date"
        class="h-9 px-3 rounded-lg border border-[var(--line)] bg-[var(--surface)] text-xs text-[var(--text)] outline-none focus:border-[var(--accent)]"
        @change="fetchList(1)"
      />
      <select
        v-model="filterStatus"
        class="h-9 px-3 rounded-lg border border-[var(--line)] bg-[var(--surface)] text-xs text-[var(--text)] outline-none focus:border-[var(--accent)]"
        @change="fetchList(1)"
      >
        <option value="">Semua Status</option>
        <option value="pending">Menunggu</option>
        <option value="confirmed">Terkonfirmasi</option>
        <option value="in_progress">Berlangsung</option>
        <option value="completed">Selesai</option>
        <option value="cancelled">Dibatalkan</option>
        <option value="no_show">Tidak Hadir</option>
      </select>
    </div>

    <BaseCard :padded="false">
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead>
            <tr class="border-b border-[var(--line)] text-[var(--muted)]">
              <th class="px-4 py-3 text-left font-semibold">Pasien</th>
              <th class="px-4 py-3 text-left font-semibold">Psikolog</th>
              <th class="px-4 py-3 text-left font-semibold">Jadwal</th>
              <th class="px-4 py-3 text-left font-semibold">Layanan</th>
              <th class="px-4 py-3 text-right font-semibold">Harga</th>
              <th class="px-4 py-3 text-center font-semibold">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="isLoading">
              <td colspan="6" class="px-4 py-10 text-center text-[var(--muted)]">Memuat data...</td>
            </tr>
            <tr v-else-if="list.length === 0">
              <td colspan="6" class="px-4 py-10 text-center text-[var(--muted)]">Tidak ada jadwal</td>
            </tr>
            <tr
              v-for="b in list" :key="b.id"
              class="border-b border-[var(--line)] last:border-0 hover:bg-[var(--muted)]/4 transition-colors"
            >
              <!-- Pasien -->
              <td class="px-4 py-3">
                <div class="flex items-center gap-2.5">
                  <div class="person-avatar">
                    <img v-if="b.pasien?.avatar" :src="b.pasien.avatar" :alt="b.pasien.name" class="h-full w-full object-cover rounded-full" />
                    <span v-else class="text-[var(--accent)] font-semibold text-[10px]">{{ initials(b.pasien?.name ?? '?') }}</span>
                  </div>
                  <span class="font-medium text-[var(--text)]">{{ b.pasien?.name ?? '—' }}</span>
                </div>
              </td>
              <!-- Psikolog -->
              <td class="px-4 py-3">
                <div class="flex items-center gap-2.5">
                  <div class="person-avatar">
                    <img v-if="b.psikolog?.avatar" :src="b.psikolog.avatar" :alt="b.psikolog.name" class="h-full w-full object-cover rounded-full" />
                    <span v-else class="text-emerald-600 font-semibold text-[10px]">{{ initials(b.psikolog?.name ?? '?') }}</span>
                  </div>
                  <div>
                    <div class="font-medium text-[var(--text)]">{{ b.psikolog?.name ?? '—' }}</div>
                    <div class="text-[var(--muted)]">{{ b.psikolog?.specialization ?? '' }}</div>
                  </div>
                </div>
              </td>
              <!-- Jadwal -->
              <td class="px-4 py-3 text-[var(--text)]">
                <div class="font-medium">{{ fmtDate(b.booking_date) }}</div>
                <div class="text-[var(--muted)]">{{ b.start_time }} – {{ b.end_time }}</div>
              </td>
              <!-- Layanan -->
              <td class="px-4 py-3 text-[var(--text)]">
                <div>{{ b.order?.category_name ?? b.requested_category?.name ?? 'Layanan konsultasi' }}</div>
                <div class="text-[var(--muted)]">
                  {{ b.order?.duration_name ?? (b.duration_minutes ? `${b.duration_minutes} menit` : 'Durasi belum ditentukan') }}
                  · {{ b.order?.consultation_type ? consultationLabel(b.order.consultation_type) : consultationLabel(b.consultation_type) }}
                </div>
              </td>
              <!-- Harga -->
              <td class="px-4 py-3 text-right font-mono text-[var(--text)]">
                <span v-if="b.order || b.estimated_price !== null">{{ fmtCurrency(b.order?.calculated_price ?? b.estimated_price ?? 0) }}</span>
                <span v-else class="text-[var(--muted)]">Belum tersedia</span>
              </td>
              <!-- Status -->
              <td class="px-4 py-3 text-center">
                <BaseBadge :tone="STATUS_COLORS[b.status] ?? 'neutral'">
                  {{ STATUS_LABELS[b.status] ?? b.status }}
                </BaseBadge>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="flex items-center justify-between border-t border-[var(--line)] px-4 py-3">
        <span class="text-xs text-[var(--muted)]">{{ meta.total }} jadwal</span>
        <div class="flex gap-1">
          <button
            v-for="p in meta.last_page" :key="p" type="button"
            class="h-7 w-7 rounded text-xs transition-colors"
            :class="p === meta.current_page ? 'bg-[var(--accent)] text-white' : 'text-[var(--muted)] hover:bg-[var(--muted)]/10'"
            @click="fetchList(p)"
          >{{ p }}</button>
        </div>
      </div>
    </BaseCard>
  </div>
</template>

<style scoped>
.person-avatar {
  width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
  background: color-mix(in srgb, var(--accent) 10%, transparent);
  display: flex; align-items: center; justify-content: center; overflow: hidden;
}
</style>
