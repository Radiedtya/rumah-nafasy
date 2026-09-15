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
  XMarkIcon,
  PlayIcon,
  StopIcon,
  ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/20/solid'

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
</script>

<template>
  <div class="max-w-5xl mx-auto space-y-6">
    <div>
      <h2 class="font-display text-2xl font-bold text-neutral-900 dark:text-neutral-100">
        Konsultasi & Catatan Klinis
      </h2>
      <p class="text-neutral-500 text-sm mt-0.5">
        Mulai sesi konsultasi pasien, bergabung ke ruang video call Jitsi, dan simpan catatan medis terenkripsi.
      </p>
    </div>

    <div v-if="message" class="p-3.5 rounded-2xl bg-emerald-50 text-emerald-700 text-xs font-semibold flex items-center gap-2">
      <CheckCircleIcon class="w-4 h-4 text-emerald-500 shrink-0" />
      {{ message }}
    </div>

    <div v-if="error" class="p-3.5 rounded-2xl bg-rose-50 text-rose-700 text-xs font-semibold">
      {{ error }}
    </div>

    <!-- Booking List -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="h-32 bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 animate-pulse" />
    </div>

    <div v-else-if="bookings.length === 0" class="bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 p-12 text-center text-neutral-400">
      <p class="text-4xl mb-2">📋</p>
      <p class="font-medium text-neutral-700 dark:text-neutral-200 text-sm">Belum ada antrean booking</p>
      <p class="text-xs text-neutral-500 mt-1">Booking dari pasien yang memilih jadwal Anda akan tampil di sini.</p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="booking in bookings"
        :key="booking.id"
        class="bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200/80 dark:border-neutral-800 p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6"
      >
        <div class="space-y-3">
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
          </div>

          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-sky-400 to-indigo-400 text-white font-bold flex items-center justify-center text-base shrink-0 shadow-xs">
              {{ booking.pasien?.name?.charAt(0) || 'U' }}
            </div>
            <div>
              <h3 class="font-display font-bold text-neutral-900 dark:text-neutral-100 text-base">
                {{ booking.pasien?.name }}
              </h3>
              <p class="text-xs text-neutral-500">
                Kategori: {{ booking.order?.category_name }} · {{ booking.order?.duration_name }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-4 text-xs text-neutral-600 dark:text-neutral-400 flex-wrap">
            <span class="inline-flex items-center gap-1.5 font-medium text-neutral-900 dark:text-neutral-100">
              <CalendarDaysIcon class="w-4 h-4 text-neutral-400" />
              {{ booking.booking_date }}
            </span>
            <span class="inline-flex items-center gap-1.5 font-medium text-rose-600">
              <ClockIcon class="w-4 h-4 text-rose-400" />
              {{ booking.start_time }} - {{ booking.end_time }}
            </span>
          </div>
        </div>

        <!-- Action buttons for Psikolog -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 shrink-0">
          <!-- Video Call -->
          <a
            v-if="booking.room_id"
            :href="`https://meet.jit.si/${booking.room_id}`"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 transition-colors shadow-xs"
          >
            <VideoCameraIcon class="w-3.5 h-3.5" />
            <span>Ruang Video</span>
            <ArrowTopRightOnSquareIcon class="w-3 h-3" />
          </a>

          <!-- Start consultation -->
          <button
            v-if="booking.status === 'confirmed' && !booking.consultation"
            type="button"
            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-sky-600 text-white text-xs font-semibold hover:bg-sky-700 transition-colors shadow-xs"
            @click="startConsultation(booking)"
          >
            <PlayIcon class="w-3.5 h-3.5" />
            Mulai Sesi
          </button>

          <!-- Notes button -->
          <button
            v-if="booking.consultation"
            type="button"
            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl border border-neutral-200 dark:border-neutral-800 text-xs font-semibold text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors"
            @click="openNotes(booking.consultation)"
          >
            <DocumentTextIcon class="w-3.5 h-3.5 text-rose-500" />
            Catatan Klinis
          </button>

          <!-- End consultation -->
          <button
            v-if="booking.status === 'in_progress' && booking.consultation?.status === 'in_progress'"
            type="button"
            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 transition-colors shadow-xs"
            @click="endConsultation(booking.consultation.id)"
          >
            <StopIcon class="w-3.5 h-3.5" />
            Selesaikan Sesi
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL CATATAN KLINIS (ENCRYPTED) ================= -->
    <div
      v-if="notesModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
    >
      <div class="bg-white dark:bg-neutral-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-neutral-200 dark:border-neutral-800 space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
          <div>
            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded uppercase tracking-wider">
              <LockClosedIcon class="w-3 h-3" />
              Enkripsi AES-256 (UU PDP)
            </span>
            <h3 class="font-display text-base font-bold text-neutral-900 dark:text-neutral-100 mt-1">
              Catatan Rekam Medis Konsultasi
            </h3>
          </div>
          <button type="button" class="p-1 text-neutral-400 hover:text-neutral-600" @click="notesModalOpen = false">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Notes history -->
        <div class="max-h-52 overflow-y-auto space-y-2 p-1">
          <div v-if="loadingNotes" class="py-6 text-center text-xs text-neutral-400">
            Memuat catatan terenkripsi...
          </div>
          <div v-else-if="notesList.length === 0" class="p-4 bg-neutral-50 dark:bg-neutral-800/40 rounded-xl text-center text-xs text-neutral-400">
            Belum ada catatan klinis pada konsultasi ini.
          </div>
          <div
            v-for="note in notesList"
            :key="note.id"
            class="p-3 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-100 dark:border-neutral-800 text-xs space-y-1"
          >
            <div class="flex items-center justify-between text-[10px] text-neutral-400">
              <span>dr. {{ note.psikolog?.name || 'Psikolog' }}</span>
              <span>{{ note.created_at ? new Date(note.created_at).toLocaleString('id-ID') : '' }}</span>
            </div>
            <p class="text-neutral-800 dark:text-neutral-200 whitespace-pre-wrap leading-relaxed">
              {{ note.content }}
            </p>
          </div>
        </div>

        <!-- Add note input -->
        <div class="pt-2 border-t border-neutral-100 dark:border-neutral-800 space-y-2">
          <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 block">
            Tambah Catatan Baru:
          </label>
          <textarea
            v-model="newNoteContent"
            rows="3"
            placeholder="Tuliskan catatan observasi, diagnosis awal, atau rekomendasi terapi..."
            class="w-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl p-3 text-xs outline-none focus:border-rose-500"
          />
          <button
            type="button"
            class="w-full py-2.5 rounded-xl bg-rose-500 text-white text-xs font-bold hover:bg-rose-600 transition-colors shadow-xs"
            :disabled="isSavingNote || !newNoteContent.trim()"
            @click="saveNote"
          >
            {{ isSavingNote ? 'Menyimpan (Enkripsi)...' : 'Simpan Catatan Terenkripsi' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
