<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiFetch } from '../../lib/api'
import {
  CalendarDaysIcon,
  ClockIcon,
  VideoCameraIcon,
  DocumentTextIcon,
  CheckCircleIcon,
  LockClosedIcon,
  PlayIcon,
  StopIcon,
  ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline'
import PageHeader from '../../components/dashboard/PageHeader.vue'
import StatusPill from '../../components/dashboard/StatusPill.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseAvatar from '../../components/ui/BaseAvatar.vue'
import BaseBadge from '../../components/ui/BaseBadge.vue'
import BaseSkeleton from '../../components/ui/BaseSkeleton.vue'
import BaseEmpty from '../../components/ui/BaseEmpty.vue'
import BaseModal from '../../components/ui/BaseModal.vue'

const bookings = ref<any[]>([])
const loading = ref(true)

// Modal Catatan Klinis
const notesModalOpen = ref(false)
const selectedConsultation = ref<any>(null)
const notesList = ref<any[]>([])
const newNoteContent = ref('')
const loadingNotes = ref(false)
const isSavingNote = ref(false)
const message = ref('')
const error = ref('')

async function fetchBookings() {
  loading.value = true
  try {
    const res = await apiFetch('psikolog/bookings')
    bookings.value = res.data?.data || res.data || []
  } catch (e) {
    console.error('Failed fetching psikolog bookings', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchBookings()
})

async function startConsultation(booking: any) {
  try {
    await apiFetch(`psikolog/bookings/${booking.id}/consultation/start`, {
      method: 'POST',
    })
    message.value = 'Sesi konsultasi resmi dimulai!'
    await fetchBookings()
  } catch (err: any) {
    error.value = err.message || 'Gagal memulai konsultasi'
  }
}

async function endConsultation(consultationId: number) {
  if (!confirm('Apakah sesi konsultasi ini sudah selesai?')) return
  try {
    await apiFetch(`psikolog/consultations/${consultationId}/end`, {
      method: 'POST',
    })
    message.value = 'Konsultasi telah diselesaikan. Terima kasih atas sesi ini!'
    await fetchBookings()
  } catch (err: any) {
    error.value = err.message || 'Gagal menyelesaikan konsultasi'
  }
}

async function openNotes(consultation: any) {
  selectedConsultation.value = consultation
  notesModalOpen.value = true
  newNoteContent.value = ''
  loadingNotes.value = true
  error.value = ''

  try {
    const res = await apiFetch(`psikolog/consultations/${consultation.id}/notes`)
    notesList.value = res.data || []
  } catch (err: any) {
    error.value = err.message || 'Gagal memuat catatan'
  } finally {
    loadingNotes.value = false
  }
}

async function saveNote() {
  if (!newNoteContent.value.trim()) return
  isSavingNote.value = true
  error.value = ''

  try {
    await apiFetch(`psikolog/consultations/${selectedConsultation.value.id}/notes`, {
      method: 'POST',
      body: JSON.stringify({
        content: newNoteContent.value,
      }),
    })
    newNoteContent.value = ''
    const res = await apiFetch(`psikolog/consultations/${selectedConsultation.value.id}/notes`)
    notesList.value = res.data || []
  } catch (err: any) {
    error.value = err.message || 'Gagal menyimpan catatan'
  } finally {
    isSavingNote.value = false
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
      title="Konsultasi & Catatan Klinis"
      description="Mulai sesi konsultasi pasien, bergabung ke ruang video call Jitsi, dan simpan catatan medis terenkripsi."
    />

    <div
      v-if="message"
      class="mb-4 flex items-center gap-2 rounded-xl bg-emerald-500/10 px-3.5 py-2.5 text-xs font-medium text-emerald-600 dark:text-emerald-400"
    >
      <CheckCircleIcon class="h-4 w-4 shrink-0" />
      {{ message }}
    </div>

    <div v-if="error && !notesModalOpen" class="mb-4 rounded-xl bg-rose-500/10 px-3.5 py-2.5 text-xs text-rose-600 dark:text-rose-400">
      {{ error }}
    </div>

    <!-- Booking List -->
    <div v-if="loading" class="space-y-3">
      <BaseSkeleton v-for="i in 3" :key="i" class="h-28" />
    </div>

    <BaseCard v-else-if="bookings.length === 0" :padded="false">
      <BaseEmpty
        icon="📋"
        title="Belum ada antrean booking"
        description="Booking dari pasien yang memilih jadwal Anda akan tampil di sini."
      />
    </BaseCard>

    <div v-else class="space-y-3">
      <BaseCard
        v-for="booking in bookings"
        :key="booking.id"
        class="flex flex-col justify-between gap-5 md:flex-row md:items-center"
      >
        <div class="min-w-0 space-y-3">
          <div class="flex flex-wrap items-center gap-2">
            <StatusPill :status="booking.status" />
            <span class="font-mono text-[10px] text-[var(--muted)]">
              {{ booking.order?.order_number }}
            </span>
          </div>

          <div class="flex items-center gap-3">
            <BaseAvatar :name="booking.pasien?.name || 'U'" size="md" />
            <div class="min-w-0">
              <h3 class="truncate text-sm font-semibold text-[var(--text)]">
                {{ booking.pasien?.name }}
              </h3>
              <p class="truncate text-[11px] text-[var(--muted)]">
                {{ booking.order?.category_name }} · {{ booking.order?.duration_name }}
              </p>
            </div>
          </div>

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
        </div>

        <!-- Actions -->
        <div class="flex shrink-0 flex-col items-stretch gap-2 sm:flex-row sm:items-center">
          <a
            v-if="booking.room_id"
            :href="`https://meet.jit.si/${booking.room_id}`"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white transition-colors hover:bg-emerald-700"
          >
            <VideoCameraIcon class="h-3.5 w-3.5" />
            Ruang Video
            <ArrowTopRightOnSquareIcon class="h-3 w-3" />
          </a>

          <BaseButton
            v-if="booking.status === 'confirmed' && !booking.consultation"
            variant="secondary"
            size="sm"
            @click="startConsultation(booking)"
          >
            <PlayIcon class="h-3.5 w-3.5 text-[var(--muted)]" />
            Mulai Sesi
          </BaseButton>

          <BaseButton
            v-if="booking.consultation"
            variant="secondary"
            size="sm"
            @click="openNotes(booking.consultation)"
          >
            <DocumentTextIcon class="h-3.5 w-3.5 text-[var(--accent)]" />
            Catatan Klinis
          </BaseButton>

          <BaseButton
            v-if="booking.status === 'in_progress' && booking.consultation?.status === 'in_progress'"
            variant="danger"
            size="sm"
            @click="endConsultation(booking.consultation.id)"
          >
            <StopIcon class="h-3.5 w-3.5" />
            Selesaikan
          </BaseButton>
        </div>
      </BaseCard>
    </div>

    <!-- ================= MODAL CATATAN KLINIS ================= -->
    <BaseModal
      v-model:open="notesModalOpen"
      title="Catatan Rekam Medis Konsultasi"
      max-width="max-w-lg"
    >
      <template #description>
        <BaseBadge tone="success">
          <LockClosedIcon class="h-3 w-3" />
          Enkripsi AES-256 (UU PDP)
        </BaseBadge>
      </template>

      <div class="space-y-4">
        <!-- Notes history -->
        <div class="max-h-52 space-y-2 overflow-y-auto">
          <div v-if="loadingNotes" class="py-6 text-center text-xs text-[var(--muted)]">
            Memuat catatan terenkripsi…
          </div>
          <div
            v-else-if="notesList.length === 0"
            class="rounded-xl bg-[var(--muted)]/5 py-6 text-center text-xs text-[var(--muted)]"
          >
            Belum ada catatan klinis pada konsultasi ini.
          </div>
          <div
            v-for="note in notesList"
            :key="note.id"
            class="rounded-xl border border-[var(--line)] p-3 text-xs"
          >
            <div class="mb-1 flex items-center justify-between text-[10px] text-[var(--muted)]">
              <span>dr. {{ note.psikolog?.name || 'Psikolog' }}</span>
              <span>{{ note.created_at ? new Date(note.created_at).toLocaleString('id-ID') : '' }}</span>
            </div>
            <p class="whitespace-pre-wrap leading-relaxed text-[var(--text)]">
              {{ note.content }}
            </p>
          </div>
        </div>

        <!-- Add note -->
        <div class="space-y-2 border-t border-[var(--line)] pt-3">
          <label class="field-label">Tambah Catatan Baru</label>
          <textarea
            v-model="newNoteContent"
            rows="3"
            placeholder="Tuliskan catatan observasi, diagnosis awal, atau rekomendasi terapi…"
            class="field-input resize-none"
          />
          <BaseButton
            size="sm"
            class="w-full"
            :disabled="isSavingNote || !newNoteContent.trim()"
            @click="saveNote"
          >
            {{ isSavingNote ? 'Menyimpan (Enkripsi)…' : 'Simpan Catatan Terenkripsi' }}
          </BaseButton>
        </div>
      </div>
    </BaseModal>
  </div>
</template>
