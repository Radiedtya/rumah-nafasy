<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { marked } from 'marked'
import DOMPurify from 'dompurify'
import {
  ArrowLeftIcon,
  VideoCameraIcon,
  MapPinIcon,
  BanknotesIcon,
  DocumentTextIcon,
  ArrowPathIcon,
  XCircleIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  LockClosedIcon,
  ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline'
import { apiFetch } from '../../lib/api'
import StatusPill from '../../components/dashboard/StatusPill.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseAvatar from '../../components/ui/BaseAvatar.vue'
import BaseModal from '../../components/ui/BaseModal.vue'

/**
 * Detail sesi pasien:
 * - Progres pengajuan (timeline status)
 * - Tombol masuk video call (hanya bila sudah disetujui & bukan offline)
 * - Detail pesanan (jenis, kategori, durasi, tarif acuan)
 * - Keluhan Markdown yang dikirim ke psikolog
 * - Aksi: reschedule & batalkan (pindahan dari halaman daftar)
 */

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const notFound = ref(false)
const booking = ref<any>(null)
const actionError = ref('')

// ── Modal Reschedule ─────────────────────────────────────────────────────────
const rescheduleOpen = ref(false)
const rescheduleDate = ref('')
const rescheduleSlots = ref<any[]>([])
const selectedNewSlot = ref<any>(null)
const rescheduleReason = ref('')
const loadingRescheduleSlots = ref(false)
const isSubmittingReschedule = ref(false)
const rescheduleError = ref('')

// ── Modal Cancel ─────────────────────────────────────────────────────────────
const cancelOpen = ref(false)
const cancelReason = ref('')
const isSubmittingCancel = ref(false)
const cancelError = ref('')

async function loadBooking() {
  loading.value = true
  notFound.value = false
  try {
    const res = await apiFetch(`pasien/bookings/${route.params.id}`)
    booking.value = res.data
    if (!booking.value) notFound.value = true
  } catch (e) {
    console.error('Failed loading booking detail', e)
    notFound.value = true
  } finally {
    loading.value = false
  }
}

let pollingTimer: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  loadBooking()
  // Polling realtime setiap 30 detik
  pollingTimer = setInterval(() => loadBooking(), 30_000)
})

onUnmounted(() => {
  if (pollingTimer) clearInterval(pollingTimer)
})

// ── Timeline progres ────────────────────────────────────────────────────────
const progressSteps = computed(() => {
  const b = booking.value
  if (!b) return []
  const rejected = b.status === 'rejected'
  const cancelled = b.status === 'cancelled'
  return [
    {
      key: 'submitted',
      label: 'Pengajuan Terkirim',
      desc: 'Slot ditahan untuk Anda',
      done: true,
      failed: false,
    },
    {
      key: 'approved',
      label: rejected ? 'Ditolak Psikolog' : 'Disetujui Psikolog',
      desc: rejected
        ? b.rejected_reason || 'Tanpa alasan'
        : 'Jadwal resmi terkonfirmasi',
      done: ['confirmed', 'in_progress', 'completed'].includes(b.status),
      failed: rejected,
    },
    {
      key: 'session',
      label: 'Sesi Konsultasi',
      desc: 'Video call / tatap muka berlangsung',
      done: ['in_progress', 'completed'].includes(b.status),
      failed: cancelled,
    },
    {
      key: 'done',
      label: 'Selesai & Pembayaran',
      desc: 'Bayar langsung ke psikolog (P2P)',
      done: b.status === 'completed',
      failed: false,
    },
  ]
})

const isAwaitingApproval = computed(() => booking.value?.status === 'pending_psikolog')
const canJoinVideo = computed(
  () =>
    booking.value?.room_id &&
    booking.value?.consultation_type !== 'offline' &&
    ['confirmed', 'in_progress'].includes(booking.value?.status),
)

// ── Keluhan Markdown (render disanitasi) ────────────────────────────────────
const complaintHtml = computed(() => {
  const md = booking.value?.complaint_markdown
  if (!md || !md.trim()) return ''
  const raw = marked.parse(md, { async: false, gfm: true, breaks: true })
  return DOMPurify.sanitize(raw, { USE_PROFILES: { html: true } })
})

// ── Info pesanan ────────────────────────────────────────────────────────────
const typeLabel = computed(() =>
  booking.value?.consultation_type === 'offline' ? 'Offline (Tatap Muka)' : 'Video Call',
)

const categoryLabel = computed(
  () => booking.value?.requested_category?.name || booking.value?.order?.category_name || 'Fleksibel',
)

const estimateLabel = computed(() => {
  const cat = booking.value?.requested_category
  if (cat?.base_price) return `~ ${formatRupiah(Number(cat.base_price))} / 60m`
  if (booking.value?.psikolog?.custom_rate) return `~ ${formatRupiah(Number(booking.value.psikolog.custom_rate))} / 60m`
  return 'Disepakati dengan psikolog'
})

function formatRupiah(num: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(num ?? 0)
}

function formatDate(d: string) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

// ── Reschedule ──────────────────────────────────────────────────────────────
function openReschedule() {
  const b = booking.value
  if (!b) return
  const fallback = new Date()
  fallback.setDate(fallback.getDate() + 2)
  rescheduleDate.value = b.booking_date ?? fallback.toISOString().split('T')[0]
  rescheduleReason.value = ''
  rescheduleError.value = ''
  rescheduleOpen.value = true
  loadRescheduleSlots()
}

async function loadRescheduleSlots() {
  const b = booking.value
  if (!b || !rescheduleDate.value) return
  loadingRescheduleSlots.value = true
  rescheduleSlots.value = []
  selectedNewSlot.value = null
  try {
    const dur = b.duration_minutes || b.order?.duration_minutes || 60
    const res = await apiFetch(
      `pasien/psikolog/${b.psikolog.id}/slots?date=${rescheduleDate.value}&duration_minutes=${dur}`,
    )
    rescheduleSlots.value = res.data?.available_slots || []
    if (rescheduleSlots.value.length > 0) selectedNewSlot.value = rescheduleSlots.value[0]
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
    await apiFetch(`pasien/bookings/${booking.value.id}/reschedule`, {
      method: 'PUT',
      body: JSON.stringify({
        booking_date: rescheduleDate.value,
        start_time: selectedNewSlot.value.start_time,
        reason: rescheduleReason.value || 'Reschedule oleh pasien',
      }),
    })
    rescheduleOpen.value = false
    await loadBooking()
  } catch (e: any) {
    rescheduleError.value = e.message || 'Gagal melakukan reschedule'
  } finally {
    isSubmittingReschedule.value = false
  }
}

// ── Cancel ──────────────────────────────────────────────────────────────────
function openCancel() {
  cancelReason.value = ''
  cancelError.value = ''
  cancelOpen.value = true
}

async function submitCancel() {
  isSubmittingCancel.value = true
  cancelError.value = ''
  try {
    await apiFetch(`pasien/bookings/${booking.value.id}/cancel`, {
      method: 'POST',
      body: JSON.stringify({ reason: cancelReason.value || 'Dibatalkan oleh pasien' }),
    })
    cancelOpen.value = false
    await loadBooking()
  } catch (e: any) {
    cancelError.value = e.message || 'Gagal membatalkan booking'
  } finally {
    isSubmittingCancel.value = false
  }
}

function backToList() {
  router.push('/dashboard/sesi')
}
</script>

<template>
  <div>
    <!-- Loading -->
    <div v-if="loading" class="space-y-4">
      <div class="h-6 w-40 animate-pulse rounded bg-[var(--muted)]/10" />
      <div class="h-40 animate-pulse rounded-2xl bg-[var(--muted)]/10" />
      <div class="h-24 animate-pulse rounded-2xl bg-[var(--muted)]/10" />
    </div>

    <!-- Not found -->
    <BaseCard v-else-if="notFound || !booking" class="text-center">
      <p class="text-sm font-semibold text-[var(--text)]">Sesi tidak ditemukan</p>
      <p class="mx-auto mt-1 max-w-sm text-xs text-[var(--muted)]">
        Sesi tidak tersedia atau bukan milik akun Anda.
      </p>
      <BaseButton size="sm" variant="secondary" class="mt-4" @click="backToList">
        Kembali ke Sesi Saya
      </BaseButton>
    </BaseCard>

    <div v-else class="space-y-5">
      <!-- Header -->
      <div class="flex items-start justify-between gap-4">
        <div>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 text-xs font-medium text-[var(--muted)] transition-colors hover:text-[var(--text)]"
            @click="backToList"
          >
            <ArrowLeftIcon class="h-3.5 w-3.5" />
            Sesi Saya
          </button>
          <h1 class="mt-1.5 flex flex-wrap items-center gap-2.5 text-lg font-semibold tracking-tight text-[var(--text)]">
            Sesi Konsultasi
            <StatusPill :status="booking.status" />
          </h1>
        </div>
      </div>

      <div v-if="actionError" class="rounded-xl bg-rose-500/10 px-3.5 py-2.5 text-xs text-rose-600 dark:text-rose-400">
        {{ actionError }}
      </div>

      <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
        <!-- ── Kolom kiri ── -->
        <div class="min-w-0 space-y-5">
          <!-- Progres pengajuan -->
          <BaseCard>
            <h2 class="text-sm font-semibold text-[var(--text)]">Progres Pengajuan</h2>

            <!-- Info menunggu -->
            <div
              v-if="isAwaitingApproval"
              class="mt-3 flex items-start gap-2.5 rounded-xl border border-amber-500/30 bg-amber-500/8 p-3.5"
            >
              <ExclamationTriangleIcon class="mt-0.5 h-4.5 w-4.5 shrink-0 text-amber-600 dark:text-amber-400" />
              <p class="text-[11px] leading-relaxed text-amber-800 dark:text-amber-300">
                <strong>Menunggu persetujuan psikolog.</strong> Slot sudah ditahan untuk Anda —
                psikolog akan meninjau beserta keluhan yang Anda kirim. Tidak ada pembayaran sekarang.
              </p>
            </div>
            <div
              v-else-if="booking.status === 'rejected'"
              class="mt-3 flex items-start gap-2.5 rounded-xl border border-rose-500/30 bg-rose-500/8 p-3.5"
            >
              <XCircleIcon class="mt-0.5 h-4.5 w-4.5 shrink-0 text-rose-600 dark:text-rose-400" />
              <p class="text-[11px] leading-relaxed text-rose-700 dark:text-rose-300">
                <strong>Pengajuan ditolak.</strong>
                <span v-if="booking.rejected_reason">Alasan: “{{ booking.rejected_reason }}”.</span>
                Slot sudah dilepas — silakan buat pengajuan baru.
              </p>
            </div>

            <!-- Timeline -->
            <ol class="mt-5 space-y-0">
              <li
                v-for="(step, i) in progressSteps"
                :key="step.key"
                class="relative flex gap-3.5 pb-6 last:pb-0"
              >
                <!-- Connector -->
                <span
                  v-if="i < progressSteps.length - 1"
                  class="absolute top-7 left-[13px] h-full w-0.5"
                  :class="step.done && !step.failed ? 'bg-[var(--accent)]' : 'bg-[var(--line)]'"
                />
                <!-- Dot -->
                <span
                  class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border text-[11px] font-semibold"
                  :class="
                    step.failed
                      ? 'border-rose-500 bg-rose-500/10 text-rose-600 dark:text-rose-400'
                      : step.done
                        ? 'border-[var(--accent)] bg-[var(--accent)] text-white'
                        : 'border-[var(--line)] bg-[var(--surface)] text-[var(--muted)]'
                  "
                >
                  <XCircleIcon v-if="step.failed" class="h-4 w-4" />
                  <CheckCircleIcon v-else-if="step.done" class="h-4 w-4" />
                  <template v-else>{{ i + 1 }}</template>
                </span>
                <div class="min-w-0 pt-0.5">
                  <p
                    class="text-xs font-semibold"
                    :class="step.failed ? 'text-rose-600 dark:text-rose-400' : step.done ? 'text-[var(--text)]' : 'text-[var(--muted)]'"
                  >
                    {{ step.label }}
                  </p>
                  <p class="mt-0.5 text-[11px] leading-relaxed text-[var(--muted)]">{{ step.desc }}</p>
                </div>
              </li>
            </ol>
          </BaseCard>

          <!-- Keluhan yang dikirim -->
          <BaseCard v-if="booking.complaint_markdown">
            <div class="flex items-center justify-between gap-3">
              <h2 class="flex items-center gap-2 text-sm font-semibold text-[var(--text)]">
                <DocumentTextIcon class="h-4 w-4 text-[var(--accent)]" />
                Keluhan &amp; Catatan Anda
              </h2>
              <span class="text-[10px] text-[var(--muted)]">hanya terlihat psikolog</span>
            </div>
            <div
              class="markdown-body mt-3 rounded-xl border border-[var(--line)] bg-[var(--muted)]/4 px-4 py-3 text-sm leading-relaxed text-[var(--text)]"
            >
              <!-- eslint-disable-next-line vue/no-v-html — sudah disanitasi DOMPurify -->
              <div v-html="complaintHtml" />
            </div>
          </BaseCard>
        </div>

        <!-- ── Kolom kanan ── -->
        <div class="space-y-4 lg:sticky lg:top-4">
          <!-- Psikolog -->
          <BaseCard>
            <p class="text-[11px] font-semibold uppercase tracking-wide text-[var(--muted)]">Psikolog</p>
            <div class="mt-3 flex items-center gap-3">
              <BaseAvatar :name="booking.psikolog?.name || 'P'" :src="booking.psikolog?.avatar" size="md" />
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-[var(--text)]">{{ booking.psikolog?.name }}</p>
                <p class="truncate text-xs text-[var(--accent)]">
                  {{ booking.psikolog?.specialization || 'Psikolog Terverifikasi' }}
                </p>
              </div>
            </div>
          </BaseCard>

          <!-- Detail pesanan -->
          <BaseCard>
            <p class="text-[11px] font-semibold uppercase tracking-wide text-[var(--muted)]">Detail Pesanan</p>
            <dl class="mt-3 space-y-2.5 text-xs">
              <div class="flex items-center justify-between gap-3">
                <dt class="text-[var(--muted)]">Jenis</dt>
                <dd class="font-medium text-[var(--text)]">{{ typeLabel }}</dd>
              </div>
              <div class="flex items-center justify-between gap-3">
                <dt class="text-[var(--muted)]">Kategori</dt>
                <dd class="font-medium text-[var(--text)]">{{ categoryLabel }}</dd>
              </div>
              <div class="flex items-center justify-between gap-3">
                <dt class="text-[var(--muted)]">Durasi</dt>
                <dd class="font-medium tabular-nums text-[var(--text)]">{{ booking.duration_minutes ?? 60 }} menit</dd>
              </div>
              <div class="flex items-start justify-between gap-3">
                <dt class="shrink-0 text-[var(--muted)]">Jadwal</dt>
                <dd class="text-right font-medium text-[var(--text)]">
                  {{ formatDate(booking.booking_date) }}
                  <span class="block tabular-nums text-[var(--muted)]">
                    {{ booking.start_time }}–{{ booking.end_time }} WIB
                  </span>
                </dd>
              </div>
              <div class="flex items-center justify-between gap-3 border-t border-[var(--line)] pt-2.5">
                <dt class="text-[var(--muted)]">Acuan Biaya</dt>
                <dd class="tabular-nums font-medium text-[var(--text)]">{{ estimateLabel }}</dd>
              </div>
              <div v-if="booking.order?.order_number" class="flex items-center justify-between gap-3">
                <dt class="text-[var(--muted)]">No. Pesanan</dt>
                <dd class="font-mono text-[10px] text-[var(--muted)]">{{ booking.order.order_number }}</dd>
              </div>
            </dl>

            <div class="mt-3 flex items-start gap-2 rounded-lg bg-[var(--muted)]/6 p-2.5">
              <BanknotesIcon class="mt-0.5 h-4 w-4 shrink-0 text-[var(--accent)]" />
              <p class="text-[11px] leading-relaxed text-[var(--muted)]">
                Pembayaran dilakukan <strong class="text-[var(--text)]">setelah sesi selesai</strong>,
                langsung ke psikolog — bukan lewat aplikasi.
              </p>
            </div>
          </BaseCard>

          <!-- Aksi utama -->
          <BaseCard>
            <p class="text-[11px] font-semibold uppercase tracking-wide text-[var(--muted)]">Aksi</p>
            <div class="mt-3 space-y-2">
              <!-- Video call -->
              <a
                v-if="canJoinVideo"
                :href="`https://meet.jit.si/${booking.room_id}`"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-emerald-600 py-2.5 text-xs font-semibold text-white transition-colors hover:bg-emerald-700"
              >
                <VideoCameraIcon class="h-4 w-4" />
                Masuk Video Call
                <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5" />
              </a>
              <div
                v-else-if="booking.consultation_type !== 'offline' && booking.status === 'pending_psikolog'"
                class="flex items-center gap-2 rounded-lg bg-[var(--muted)]/6 px-3 py-2.5 text-[11px] text-[var(--muted)]"
              >
                <LockClosedIcon class="h-4 w-4 shrink-0" />
                Ruang video terbuka setelah psikolog menyetujui
              </div>
              <div
                v-else-if="booking.consultation_type === 'offline' && booking.status === 'pending_psikolog'"
                class="flex items-center gap-2 rounded-lg bg-[var(--muted)]/6 px-3 py-2.5 text-[11px] text-[var(--muted)]"
              >
                <MapPinIcon class="h-4 w-4 shrink-0" />
                Detail lokasi menyusul setelah disetujui
              </div>

              <!-- Reschedule -->
              <BaseButton
                v-if="booking.can_reschedule"
                variant="secondary"
                size="md"
                class="w-full"
                @click="openReschedule"
              >
                <ArrowPathIcon class="h-3.5 w-3.5" />
                Ubah Jadwal
              </BaseButton>

              <!-- Cancel -->
              <BaseButton
                v-if="booking.can_cancel"
                variant="ghost"
                size="md"
                class="w-full !text-rose-600 dark:!text-rose-400 hover:!bg-rose-500/10"
                @click="openCancel"
              >
                <XCircleIcon class="h-3.5 w-3.5" />
                Batalkan Sesi
              </BaseButton>
            </div>
          </BaseCard>
        </div>
      </div>
    </div>

    <!-- ================= RESCHEDULE MODAL ================= -->
    <BaseModal
      v-model:open="rescheduleOpen"
      title="Ubah Jadwal Konsultasi"
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
          <label class="field-label">Alasan (opsional)</label>
          <input v-model="rescheduleReason" type="text" placeholder="Contoh: Ada keperluan mendadak" class="field-input" />
        </div>

        <BaseButton
          size="md"
          class="w-full"
          :disabled="isSubmittingReschedule || !selectedNewSlot"
          @click="submitReschedule"
        >
          {{ isSubmittingReschedule ? 'Menyimpan…' : 'Konfirmasi Jadwal Baru' }}
        </BaseButton>
      </div>
    </BaseModal>

    <!-- ================= CANCEL MODAL ================= -->
    <BaseModal
      v-model:open="cancelOpen"
      title="Batalkan Sesi Konsultasi"
      max-width="max-w-md"
    >
      <div class="space-y-4">
        <div v-if="cancelError" class="rounded-lg bg-rose-500/10 px-3 py-2 text-xs text-rose-600 dark:text-rose-400">
          {{ cancelError }}
        </div>

        <div v-if="booking.order" class="rounded-xl border border-[var(--line)] bg-[var(--muted)]/5 p-4">
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
            placeholder="Tuliskan alasan pembatalan…"
            class="field-input resize-none"
          />
        </div>

        <div class="flex items-center gap-2">
          <BaseButton variant="secondary" size="md" class="flex-1" @click="cancelOpen = false">
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

<style scoped>
/* Render Markdown keluhan — konsisten dengan MarkdownEditor. */
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
  margin: 0.45em 0;
}
.markdown-body :deep(ul),
.markdown-body :deep(ol) {
  margin: 0.45em 0;
  padding-left: 1.4em;
}
.markdown-body :deep(ul) {
  list-style: disc;
}
.markdown-body :deep(ol) {
  list-style: decimal;
}
.markdown-body :deep(li) {
  margin: 0.2em 0;
}
.markdown-body :deep(strong) {
  font-weight: 600;
}
.markdown-body :deep(code) {
  background: color-mix(in srgb, var(--muted) 12%, transparent);
  border-radius: 4px;
  padding: 0.1em 0.35em;
  font-size: 0.8em;
}
.markdown-body :deep(blockquote) {
  border-left: 3px solid var(--line);
  padding-left: 0.8em;
  margin: 0.5em 0;
  color: var(--muted);
}
</style>
