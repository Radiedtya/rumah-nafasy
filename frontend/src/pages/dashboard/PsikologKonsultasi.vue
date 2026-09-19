<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { marked } from 'marked'
import DOMPurify from 'dompurify'
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
  ChevronDownIcon,
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

// ── Persetujuan pengajuan booking (alur tanpa pembayaran) ─────────────────
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
  error.value = ''
  try {
    await apiFetch(`psikolog/bookings/${booking.id}/status`, {
      method: 'PUT',
      body: JSON.stringify({ status: 'confirmed' }),
    })
    message.value = `Pengajuan dari ${booking.pasien?.name || 'pasien'} disetujui — pasien telah diberi tahu via WhatsApp.`
    await fetchBookings()
  } catch (err: any) {
    error.value = err.message || 'Gagal menyetujui pengajuan'
  } finally {
    decidingId.value = null
  }
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
    message.value = 'Pengajuan ditolak — pasien telah diberi tahu beserta alasan.'
    await fetchBookings()
  } catch (err: any) {
    error.value = err.message || 'Gagal menolak pengajuan'
  } finally {
    decidingId.value = null
  }
}

// ── Keluhan pasien (Markdown → render disanitasi) ──────────────────────
const expandedComplaints = ref<Record<number, boolean>>({})

function toggleComplaint(id: number) {
  expandedComplaints.value[id] = !expandedComplaints.value[id]
}

function complaintHtml(md: string | null | undefined): string {
  if (!md || !md.trim()) return ''
  const raw = marked.parse(md, { async: false, gfm: true, breaks: true })
  return DOMPurify.sanitize(raw, { USE_PROFILES: { html: true } })
}

function complaintPreview(md: string | null | undefined): string {
  if (!md) return ''
  return md.replace(/[#*`>\-_]/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 80)
}

function categoryLabel(booking: any): string {
  return booking.requested_category?.name || booking.order?.category_name || 'Kategori fleksibel'
}

function durationLabel(booking: any): string {
  const m = booking.duration_minutes ?? booking.order?.duration_minutes
  return m ? `${m} Menit` : '60 Menit'
}

function typeLabel(booking: any): string {
  return booking.consultation_type === 'offline' ? 'Offline · Tatap Muka' : 'Video Call'
}

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
            <span
              v-if="booking.order?.order_number"
              class="font-mono text-[10px] text-[var(--muted)]"
            >
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
                {{ typeLabel(booking) }} · {{ categoryLabel(booking) }} · {{ durationLabel(booking) }}
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

          <!-- Keluhan pasien: preview + expand -->
          <div v-if="booking.complaint_markdown" class="rounded-xl border border-[var(--line)] bg-[var(--muted)]/4 p-3">
            <button
              type="button"
              class="flex w-full items-center gap-2 text-left"
              @click="toggleComplaint(booking.id)"
            >
              <DocumentTextIcon class="h-3.5 w-3.5 shrink-0 text-[var(--accent)]" />
              <span class="min-w-0 flex-1 truncate text-[11px] italic text-[var(--muted)]">
                Keluhan pasien: “{{ complaintPreview(booking.complaint_markdown) }}…”
              </span>
              <ChevronDownIcon
                class="h-3.5 w-3.5 shrink-0 text-[var(--muted)] transition-transform"
                :class="expandedComplaints[booking.id] ? 'rotate-180' : ''"
              />
            </button>
            <div
              v-if="expandedComplaints[booking.id]"
              class="markdown-body mt-2.5 border-t border-[var(--line)] pt-2.5 text-xs leading-relaxed text-[var(--text)]"
            >
              <!-- eslint-disable-next-line vue/no-v-html — sudah disanitasi DOMPurify -->
              <div v-html="complaintHtml(booking.complaint_markdown)" />
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex shrink-0 flex-col items-stretch gap-2 sm:flex-row sm:items-center">
          <!-- Pengajuan menunggu persetujuan: TERIMA / TOLAK (ruang video belum boleh) -->
          <template v-if="booking.status === 'pending_psikolog'">
            <BaseButton
              size="sm"
              :disabled="decidingId === booking.id"
              @click="decideRequest(booking, 'confirmed')"
            >
              <CheckCircleIcon class="h-3.5 w-3.5" />
              Terima Pengajuan
            </BaseButton>
            <BaseButton
              variant="ghost"
              size="sm"
              class="!text-rose-600 dark:!text-rose-400 hover:!bg-rose-500/10"
              :disabled="decidingId === booking.id"
              @click="decideRequest(booking, 'rejected')"
            >
              Tolak
            </BaseButton>
          </template>

          <template v-else>
            <a
              v-if="booking.room_id && booking.consultation_type !== 'offline' && ['confirmed', 'in_progress'].includes(booking.status)"
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
          </template>

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

    <!-- ================= MODAL TOLAK PENGAJUAN ================= -->
    <BaseModal
      v-model:open="rejectOpen"
      title="Tolak Pengajuan Konsultasi"
      max-width="max-w-md"
    >
      <div class="space-y-4">
        <p class="text-xs leading-relaxed text-[var(--muted)]">
          Pengajuan dari <strong class="text-[var(--text)]">{{ rejectTarget?.pasien?.name }}</strong>
          ({{ rejectTarget ? formatDate(rejectTarget.booking_date) : '' }}
          {{ rejectTarget?.start_time }}–{{ rejectTarget?.end_time }} WIB)
          akan ditolak dan pasien diberi tahu via WhatsApp. Slot akan dilepas.
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

<style scoped>
/* Render Markdown keluhan pasien — konsisten dengan MarkdownEditor. */
.markdown-body :deep(h1),
.markdown-body :deep(h2),
.markdown-body :deep(h3) {
  font-size: 0.875rem;
  font-weight: 600;
  margin: 0.9em 0 0.35em;
  color: var(--text);
}
.markdown-body :deep(h1:first-child),
.markdown-body :deep(h2:first-child),
.markdown-body :deep(h3:first-child) {
  margin-top: 0;
}
.markdown-body :deep(p) {
  margin: 0.4em 0;
}
.markdown-body :deep(ul),
.markdown-body :deep(ol) {
  margin: 0.4em 0;
  padding-left: 1.4em;
}
.markdown-body :deep(ul) {
  list-style: disc;
}
.markdown-body :deep(ol) {
  list-style: decimal;
}
.markdown-body :deep(strong) {
  font-weight: 600;
}
.markdown-body :deep(code) {
  background: color-mix(in srgb, var(--muted) 12%, transparent);
  border-radius: 4px;
  padding: 0.1em 0.35em;
  font-size: 0.85em;
}
</style>
