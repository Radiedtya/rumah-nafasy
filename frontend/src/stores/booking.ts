import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

/**
 * Alur bisnis baru (keputusan klien):
 * Pasien TIDAK membayar di aplikasi. Booking dibuat langsung
 * (pilih psikolog → paket → jadwal → selesai), menunggu persetujuan
 * psikolog. Pembayaran P2P ke psikolog SETELAH sesi selesai.
 * Harga kategori/tarif psikolog hanya ACUAN BUDGET — nominal akhir
 * disepakati dengan psikolog dan bisa bertambah/berkurang sesuai sesi.
 */

export type ConsultationType = 'video' | 'offline'

export function formatRupiah(num: number | null | undefined) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(num ?? 0)
}

/** State wizard booking 4 langkah (Paket → Keluhan → Jadwal → Selesai). */
export const useBookingStore = defineStore('booking', () => {
  // ── Detail psikolog yang dibooking ────────────────────────────────────────
  const psikolog = ref<any>(null)
  const loadingDetail = ref(false)
  const detailError = ref('')
  const loadedForSlug = ref('')

  // ── Paket (langkah 1) ─────────────────────────────────────────────────────
  const consultationType = ref<ConsultationType>('video')
  /** Durasi menit: preset 30/60/90 atau permintaan khusus (15–240). */
  const durationMinutes = ref<number>(60)

  // ── Keluhan & catatan (langkah 2, format Markdown) ───────────────────
  /** Keluhan pasien untuk psikolog — Markdown mentah, dirender di frontend. */
  const complaint = ref('')

  // ── Kategori klien (harga = acuan budget, bukan tagihan aplikasi) ─────
  const categories = ref<any[]>([])
  /** Kategori yang dipilih pasien — dikirim sebagai requested_category_id. */
  const requestedCategory = ref<any>(null)

  // ── Jadwal (langkah 2) ────────────────────────────────────────────────────
  const bookingDate = ref('')
  const slots = ref<any[]>([])
  const loadingSlots = ref(false)
  const selectedSlot = ref<any>(null)

  // ── Hasil (langkah 3) ─────────────────────────────────────────────────────
  const confirmedBooking = ref<any>(null)

  /** Tarif dasar psikolog — hanya informasi, bukan tagihan aplikasi. */
  const infoRate = computed(() => {
    const rate = psikolog.value?.custom_rate
    return rate ? Number(rate) : null
  })

  /** Acuan biaya: harga kategori terpilih, else tarif psikolog. */
  const estimateRate = computed(() => {
    if (requestedCategory.value) return Number(requestedCategory.value.base_price)
    return infoRate.value
  })

  /** Dipanggil parent saat masuk/menembus slug baru — reset state wizard. */
  function startFor(slug: string) {
    if (loadedForSlug.value === slug && psikolog.value) return
    loadedForSlug.value = slug
    psikolog.value = null
    detailError.value = ''
    loadingDetail.value = true
    consultationType.value = 'video'
    durationMinutes.value = 60
    complaint.value = ''
    requestedCategory.value = null
    bookingDate.value = ''
    slots.value = []
    selectedSlot.value = null
    confirmedBooking.value = null
  }

  function resetSchedule() {
    bookingDate.value = ''
    slots.value = []
    selectedSlot.value = null
  }

  return {
    psikolog,
    loadingDetail,
    detailError,
    loadedForSlug,
    consultationType,
    durationMinutes,
    complaint,
    categories,
    requestedCategory,
    bookingDate,
    slots,
    loadingSlots,
    selectedSlot,
    confirmedBooking,
    infoRate,
    estimateRate,
    startFor,
    resetSchedule,
  }
})
