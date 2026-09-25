<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { apiFetch } from '../../../lib/api'
import PageHeader from '../../../components/dashboard/PageHeader.vue'
import BaseCard from '../../../components/ui/BaseCard.vue'
import BaseBadge from '../../../components/ui/BaseBadge.vue'
import { MagnifyingGlassIcon, ClockIcon } from '@heroicons/vue/24/outline'

interface Person { id: number; name: string; avatar: string | null; specialization?: string }
interface BookingInConsultation {
  id: number; booking_date: string; start_time: string; end_time: string; status: string
  pasien: Person | null; psikolog: Person | null
  order: { order_number: string; category_name: string; duration_name: string; consultation_type: string } | null
}
interface Note { id: number; content: string; created_at: string }
interface Consultation {
  id: number; status: string
  started_at: string | null; ended_at: string | null
  booking: BookingInConsultation | null
  notes: Note[]
  created_at: string
}
interface Meta { current_page: number; last_page: number; total: number }

const list = ref<Consultation[]>([])
const meta = ref<Meta>({ current_page: 1, last_page: 1, total: 0 })
const isLoading = ref(false)
const search = ref('')
const filterStatus = ref('')

const STATUS_COLORS: Record<string, any> = {
  in_progress: 'warning', completed: 'success', cancelled: 'danger',
}
const STATUS_LABELS: Record<string, string> = {
  in_progress: 'Berlangsung', completed: 'Selesai', cancelled: 'Dibatalkan',
}

function fmtDate(d: string | null) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}
function fmtTime(d: string | null) {
  if (!d) return '—'
  return new Date(d).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}
function duration(start: string | null, end: string | null) {
  if (!start || !end) return null
  const mins = Math.round((new Date(end).getTime() - new Date(start).getTime()) / 60000)
  return `${mins} menit`
}
function initials(name: string) {
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

let currentPage = 1
async function fetchList(page = 1) {
  currentPage = page
  isLoading.value = true
  try {
    const params = new URLSearchParams({ page: String(page), per_page: '15' })
    if (search.value) params.set('search', search.value)
    if (filterStatus.value) params.set('status', filterStatus.value)
    const res = await apiFetch(`admin/consultations?${params}`)
    // paginateResponse: { success, message, data: [...], meta: {...} } — array langsung di `data`
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
</script>

<template>
  <div class="mx-auto max-w-6xl">
    <PageHeader
      title="Semua Konsultasi"
      description="Pantau seluruh sesi konsultasi yang sedang berjalan maupun sudah selesai."
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
      <select
        v-model="filterStatus"
        class="h-9 px-3 rounded-lg border border-[var(--line)] bg-[var(--surface)] text-xs text-[var(--text)] outline-none focus:border-[var(--accent)]"
        @change="fetchList(1)"
      >
        <option value="">Semua Status</option>
        <option value="in_progress">Berlangsung</option>
        <option value="completed">Selesai</option>
        <option value="cancelled">Dibatalkan</option>
      </select>
    </div>

    <div class="space-y-3">
      <div v-if="isLoading" class="py-10 text-center text-sm text-[var(--muted)]">Memuat data...</div>
      <div v-else-if="list.length === 0" class="py-10 text-center text-sm text-[var(--muted)]">Tidak ada konsultasi</div>

      <BaseCard v-for="c in list" :key="c.id" class="space-y-4">
        <!-- Header row -->
        <div class="flex flex-wrap items-start justify-between gap-4">
          <!-- Pasien ↔ Psikolog -->
          <div class="flex flex-wrap items-center gap-4">
            <!-- Pasien -->
            <div class="flex items-center gap-2.5">
              <div class="consult-avatar bg-[var(--accent)]/10">
                <img v-if="c.booking?.pasien?.avatar" :src="c.booking.pasien.avatar" :alt="c.booking.pasien.name" class="h-full w-full object-cover" />
                <span v-else class="text-[var(--accent)] font-semibold text-[11px]">{{ initials(c.booking?.pasien?.name ?? '?') }}</span>
              </div>
              <div>
                <div class="text-xs font-semibold text-[var(--text)]">{{ c.booking?.pasien?.name ?? '—' }}</div>
                <div class="text-[10px] text-[var(--muted)]">Pasien</div>
              </div>
            </div>

            <span class="text-[var(--muted)] text-xs">→</span>

            <!-- Psikolog -->
            <div class="flex items-center gap-2.5">
              <div class="consult-avatar bg-emerald-500/10">
                <img v-if="c.booking?.psikolog?.avatar" :src="c.booking.psikolog.avatar" :alt="c.booking.psikolog.name" class="h-full w-full object-cover" />
                <span v-else class="text-emerald-600 font-semibold text-[11px]">{{ initials(c.booking?.psikolog?.name ?? '?') }}</span>
              </div>
              <div>
                <div class="text-xs font-semibold text-[var(--text)]">{{ c.booking?.psikolog?.name ?? '—' }}</div>
                <div class="text-[10px] text-[var(--muted)]">{{ c.booking?.psikolog?.specialization ?? 'Psikolog' }}</div>
              </div>
            </div>
          </div>

          <!-- Status + waktu -->
          <div class="flex flex-col items-end gap-1.5">
            <BaseBadge :tone="STATUS_COLORS[c.status] ?? 'neutral'">
              {{ STATUS_LABELS[c.status] ?? c.status }}
            </BaseBadge>
            <div class="flex items-center gap-1 text-[10px] text-[var(--muted)]">
              <ClockIcon class="h-3 w-3" />
              <span>{{ fmtDate(c.booking?.booking_date ?? null) }} · {{ c.booking?.start_time }} – {{ c.booking?.end_time }}</span>
            </div>
          </div>
        </div>

        <!-- Detail row -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1 border-t border-[var(--line)]">
          <div>
            <div class="text-[10px] text-[var(--muted)] mb-0.5">Layanan</div>
            <div class="text-xs text-[var(--text)]">{{ c.booking?.order?.category_name ?? '—' }}</div>
          </div>
          <div>
            <div class="text-[10px] text-[var(--muted)] mb-0.5">Durasi</div>
            <div class="text-xs text-[var(--text)]">{{ c.booking?.order?.duration_name ?? '—' }}</div>
          </div>
          <div>
            <div class="text-[10px] text-[var(--muted)] mb-0.5">Mulai</div>
            <div class="text-xs text-[var(--text)]">{{ fmtTime(c.started_at) }}</div>
          </div>
          <div>
            <div class="text-[10px] text-[var(--muted)] mb-0.5">{{ c.ended_at ? 'Durasi Aktual' : 'Selesai' }}</div>
            <div class="text-xs text-[var(--text)]">{{ c.ended_at ? duration(c.started_at, c.ended_at) : '—' }}</div>
          </div>
        </div>

        <!-- Catatan -->
        <div v-if="c.notes?.length" class="pt-1 border-t border-[var(--line)]">
          <div class="text-[10px] font-semibold text-[var(--muted)] uppercase tracking-wide mb-2">
            Catatan Konsultasi ({{ c.notes.length }})
          </div>
          <div class="space-y-1.5">
            <div
              v-for="note in c.notes.slice(0, 2)" :key="note.id"
              class="text-xs text-[var(--text)] bg-[var(--muted)]/5 rounded-lg px-3 py-2 line-clamp-2"
            >{{ note.content }}</div>
            <div v-if="c.notes.length > 2" class="text-[10px] text-[var(--muted)]">
              +{{ c.notes.length - 2 }} catatan lainnya
            </div>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Pagination -->
    <div v-if="meta.last_page > 1" class="flex items-center justify-between mt-4">
      <span class="text-xs text-[var(--muted)]">{{ meta.total }} konsultasi</span>
      <div class="flex gap-1">
        <button
          v-for="p in meta.last_page" :key="p" type="button"
          class="h-7 w-7 rounded text-xs transition-colors"
          :class="p === meta.current_page ? 'bg-[var(--accent)] text-white' : 'text-[var(--muted)] hover:bg-[var(--muted)]/10'"
          @click="fetchList(p)"
        >{{ p }}</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.consult-avatar {
  width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; overflow: hidden;
}
</style>
