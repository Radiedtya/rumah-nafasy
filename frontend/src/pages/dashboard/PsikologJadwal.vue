<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { apiFetch } from '../../lib/api'
import {
  ClockIcon,
  PlusIcon,
  TrashIcon,
  DocumentTextIcon,
} from '@heroicons/vue/24/outline'
import PageHeader from '../../components/dashboard/PageHeader.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseSkeleton from '../../components/ui/BaseSkeleton.vue'
import BaseEmpty from '../../components/ui/BaseEmpty.vue'
import BaseModal from '../../components/ui/BaseModal.vue'
import BaseSwitch from '../../components/ui/BaseSwitch.vue'
import BaseConfirm from '../../components/ui/BaseConfirm.vue'
import { useAlert } from '../../composables/useAlert'

const { success, error: alertError } = useAlert()

const schedules = ref<any[]>([])
const loading = ref(true)
const modalOpen = ref(false)
const isSubmitting = ref(false)
const error = ref('')
const message = ref('')

// ── Permintaan booking menunggu persetujuan ─────────────────────────────
const pendingRequests = ref<any[]>([])
const loadingRequests = ref(false)
const decidingId = ref<number | null>(null)
const rejectOpen = ref(false)
const rejectTarget = ref<any>(null)
const rejectReason = ref('')

async function fetchPendingRequests() {
  loadingRequests.value = true
  try {
    const res = await apiFetch('psikolog/bookings?status=pending_psikolog&per_page=50')
    pendingRequests.value = res.data?.data || res.data || []
  } catch (e) {
    console.error('Failed fetching pending requests', e)
  } finally {
    loadingRequests.value = false
  }
}

async function approveRequest(booking: any) {
  decidingId.value = booking.id
  error.value = ''
  try {
    await apiFetch(`psikolog/bookings/${booking.id}/status`, {
      method: 'PUT',
      body: JSON.stringify({ status: 'confirmed' }),
    })
    message.value = ''
    success('Pengajuan disetujui — pasien telah dinotifikasi.', 'Berhasil')
    await Promise.all([fetchPendingRequests(), fetchSchedules()])
  } catch (e: any) {
    error.value = e.message || 'Gagal menyetujui pengajuan'
    alertError(error.value, 'Gagal')
  } finally {
    decidingId.value = null
  }
}

function openReject(booking: any) {
  rejectTarget.value = booking
  rejectReason.value = ''
  rejectOpen.value = true
}

async function submitReject() {
  if (!rejectTarget.value) return
  decidingId.value = rejectTarget.value.id
  error.value = ''
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
    message.value = ''
    success('Pengajuan ditolak — pasien telah dinotifikasi.', 'Berhasil')
    await fetchPendingRequests()
  } catch (e: any) {
    error.value = e.message || 'Gagal menolak pengajuan'
    alertError(error.value, 'Gagal')
  } finally {
    decidingId.value = null
  }
}

function formatRequestDate(d: string) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('id-ID', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
  })
}

const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']

const form = ref({
  day_of_week: 1,
  start_time: '09:00',
  end_time: '12:00',
})

async function fetchSchedules() {
  loading.value = true
  try {
    const res = await apiFetch('psikolog/schedules')
    schedules.value = res.data || []
  } catch (e) {
    console.error('Failed fetching schedules', e)
  } finally {
    loading.value = false
  }
}

let pollingTimer: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  fetchSchedules()
  fetchPendingRequests()
  // Polling realtime setiap 30 detik agar booking baru dari pasien langsung tampil
  pollingTimer = setInterval(() => {
    fetchPendingRequests()
    fetchSchedules()
  }, 30_000)
})

onUnmounted(() => {
  if (pollingTimer) clearInterval(pollingTimer)
})

async function toggleAvailable(sched: any) {
  try {
    await apiFetch(`psikolog/schedules/${sched.id}`, {
      method: 'PUT',
      body: JSON.stringify({
        is_available: !sched.is_available,
      }),
    })
    sched.is_available = !sched.is_available
  } catch (e) {
    console.error('Failed toggling schedule', e)
  }
}

async function addSchedule() {
  isSubmitting.value = true
  error.value = ''
  try {
    await apiFetch('psikolog/schedules', {
      method: 'POST',
      body: JSON.stringify(form.value),
    })
    modalOpen.value = false
    message.value = ''
    success('Jadwal praktek berhasil ditambahkan!', 'Berhasil')
    await fetchSchedules()
  } catch (err: any) {
    error.value = err.message || 'Gagal menambahkan jadwal'
    alertError(error.value, 'Gagal')
  } finally {
    isSubmitting.value = false
  }
}

// ── Hapus jadwal (dialog konfirmasi custom) ─────────────────────────────────
const deleteOpen = ref(false)
const deleteTarget = ref<any>(null)
const isDeleting = ref(false)

function askDelete(sched: any) {
  deleteTarget.value = sched
  deleteOpen.value = true
}

async function doDeleteSchedule() {
  if (!deleteTarget.value) return
  isDeleting.value = true
  try {
    await apiFetch(`psikolog/schedules/${deleteTarget.value.id}`, {
      method: 'DELETE',
    })
    deleteOpen.value = false
    deleteTarget.value = null
    success('Jadwal praktek berhasil dihapus.', 'Berhasil')
    await fetchSchedules()
  } catch (e: any) {
    console.error('Failed deleting schedule', e)
    alertError(e.message || 'Gagal menghapus jadwal', 'Gagal')
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div>
    <PageHeader
      title="Jadwal Praktek Mingguan"
      description="Atur hari dan jam praktek Anda agar pasien dapat mereservasi slot waktu dengan tepat."
    >
      <template #actions>
        <BaseButton size="sm" @click="modalOpen = true">
          <PlusIcon class="h-3.5 w-3.5" />
          Tambah Jadwal
        </BaseButton>
      </template>
    </PageHeader>

    <div
      v-if="error"
      class="mb-4 rounded-xl bg-rose-500/10 px-3.5 py-2.5 text-xs text-rose-600 dark:text-rose-400"
    >
      {{ error }}
    </div>

    <!-- ================= PERMINTAAN MENUNGGU PERSETUJUAN ================= -->
    <BaseCard v-if="!loadingRequests && pendingRequests.length > 0" class="mb-5 border-amber-500/30">
      <div class="mb-3 flex items-center gap-2">
        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-500/15 text-xs">🔔</span>
        <h2 class="text-sm font-semibold text-[var(--text)]">
          Permintaan Menunggu Persetujuan
          <span class="ml-1 rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400">
            {{ pendingRequests.length }}
          </span>
        </h2>
      </div>

      <div class="space-y-2.5">
        <div
          v-for="req in pendingRequests"
          :key="req.id"
          class="flex flex-col justify-between gap-3 rounded-xl border border-[var(--line)] p-3.5 md:flex-row md:items-center"
        >
          <div class="min-w-0">
            <p class="truncate text-xs font-semibold text-[var(--text)]">
              {{ req.pasien?.name || 'Pasien' }}
              <span class="ml-1.5 font-normal text-[10px] text-[var(--muted)]">
                {{ req.consultation_type === 'offline' ? 'Offline' : 'Video' }} · {{ req.duration_minutes ?? 60 }} menit
              </span>
            </p>
            <p class="mt-0.5 text-[11px] tabular-nums text-[var(--muted)]">
              {{ formatRequestDate(req.booking_date) }} · {{ req.start_time }}–{{ req.end_time }} WIB
            </p>
            <p
              v-if="req.complaint_markdown"
              class="mt-1.5 flex items-start gap-1.5 rounded-lg bg-[var(--muted)]/6 px-2.5 py-1.5 text-[11px] italic text-[var(--muted)]"
            >
              <DocumentTextIcon class="mt-0.5 h-3 w-3 shrink-0 text-[var(--accent)]" />
              <span class="min-w-0 flex-1 truncate">Keluhan: “{{ req.complaint_markdown.replace(/[#*`>\-_]/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 80) }}…”</span>
            </p>
          </div>

          <div class="flex shrink-0 items-center gap-2">
            <BaseButton size="sm" :disabled="decidingId === req.id" @click="approveRequest(req)">
              {{ decidingId === req.id ? 'Memproses…' : 'Setujui' }}
            </BaseButton>
            <BaseButton
              size="sm"
              variant="ghost"
              class="!text-rose-600 dark:!text-rose-400 hover:!bg-rose-500/10"
              :disabled="decidingId === req.id"
              @click="openReject(req)"
            >
              Tolak
            </BaseButton>
          </div>
        </div>
      </div>
    </BaseCard>

    <!-- Schedules list -->
    <div v-if="loading" class="space-y-3">
      <BaseSkeleton v-for="i in 4" :key="i" class="h-16" />
    </div>

    <BaseCard v-else-if="schedules.length === 0" :padded="false">
      <BaseEmpty
        icon="⏰"
        title="Belum ada jadwal praktek"
        description="Tambahkan jadwal praktek hari dan jam Anda."
      />
    </BaseCard>

    <div v-else class="space-y-2.5">
      <BaseCard
        v-for="sched in schedules"
        :key="sched.id"
        class="flex items-center justify-between gap-4 !p-4"
      >
        <div class="flex min-w-0 items-center gap-4">
          <div
            class="flex h-11 w-14 shrink-0 flex-col items-center justify-center rounded-lg bg-[var(--muted)]/8 text-[10px] font-semibold uppercase tracking-wide text-[var(--muted)]"
          >
            {{ dayNames[sched.day_of_week]?.slice(0, 3) }}
            <span class="mt-0.5 text-[9px] font-medium opacity-70">
              {{ sched.is_available ? 'Aktif' : 'Libur' }}
            </span>
          </div>
          <div class="min-w-0">
            <h4 class="text-sm font-semibold text-[var(--text)]">
              Hari {{ dayNames[sched.day_of_week] }}
            </h4>
            <p class="mt-0.5 flex items-center gap-1 text-[11px] tabular-nums text-[var(--muted)]">
              <ClockIcon class="h-3.5 w-3.5" />
              {{ sched.start_time }}–{{ sched.end_time }} WIB
            </p>
          </div>
        </div>

        <div class="flex shrink-0 items-center gap-3">
          <BaseSwitch :model-value="sched.is_available" @update:model-value="toggleAvailable(sched)" />
          <button
            type="button"
            class="rounded-lg p-2 text-[var(--muted)] transition-colors hover:bg-rose-500/10 hover:text-rose-600"
            title="Hapus jadwal"
            @click="askDelete(sched)"
          >
            <TrashIcon class="h-4 w-4" />
          </button>
        </div>
      </BaseCard>
    </div>

    <!-- Modal Add Schedule -->
    <BaseModal v-model:open="modalOpen" title="Tambah Jadwal Praktek" max-width="max-w-sm">
      <form class="space-y-4" @submit.prevent="addSchedule">
        <div v-if="error" class="rounded-lg bg-rose-500/10 px-3 py-2 text-xs text-rose-600 dark:text-rose-400">
          {{ error }}
        </div>

        <div>
          <label class="field-label">Hari Praktek</label>
          <select v-model.number="form.day_of_week" class="field-input">
            <option v-for="(day, idx) in dayNames" :key="idx" :value="idx">
              {{ day }}
            </option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="field-label">Jam Mulai</label>
            <input v-model="form.start_time" type="time" required class="field-input" />
          </div>
          <div>
            <label class="field-label">Jam Selesai</label>
            <input v-model="form.end_time" type="time" required class="field-input" />
          </div>
        </div>

        <BaseButton type="submit" size="md" class="w-full" :disabled="isSubmitting">
          {{ isSubmitting ? 'Menyimpan…' : 'Simpan Jadwal' }}
        </BaseButton>
      </form>
    </BaseModal>

    <!-- ================= DIALOG KONFIRMASI HAPUS JADWAL ================= -->
    <BaseConfirm
      v-model:open="deleteOpen"
      title="Hapus Jadwal Praktek?"
      :message="`Jadwal ${dayNames[deleteTarget?.day_of_week] ?? ''} ${deleteTarget?.start_time ?? ''}–${deleteTarget?.end_time ?? ''} akan dihapus permanen. Slot pada jadwal ini tidak dapat di-booking lagi.`"
      confirm-text="Ya, Hapus"
      tone="danger"
      :loading="isDeleting"
      @confirm="doDeleteSchedule"
    />

    <!-- ================= MODAL TOLAK PENGAJUAN ================= -->
    <BaseModal
      v-model:open="rejectOpen"
      title="Tolak Pengajuan Konsultasi"
      max-width="max-w-md"
    >
      <div class="space-y-4">
        <p class="text-xs leading-relaxed text-[var(--muted)]">
          Pengajuan dari <strong class="text-[var(--text)]">{{ rejectTarget?.pasien?.name }}</strong>
          akan ditolak dan pasien diberi tahu melalui WhatsApp.
        </p>
        <div>
          <label class="field-label">Alasan Penolakan</label>
          <textarea
            v-model="rejectReason"
            rows="3"
            maxlength="500"
            placeholder="Contoh: jadwal bentrok, sedang cuti…"
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
