import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

/**
 * Alur bisnis baru (keputusan klien):
 * Pasien TIDAK membayar di aplikasi. Booking dibuat langsung
 * (pilih psikolog → paket → jadwal → selesai), menunggu persetujuan
 * psikolog. Pembayaran P2P ke psikolog SETELAH sesi selesai.
 * Tarif psikolog hanya INFORMASI — nominal akhir disepakati/nego langsung.
 */

export type ConsultationType = 'video' | 'offline'

export function formatRupiah(num: number | null | undefined) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(num ?? 0)
}

/** State wizard booking 3 langkah (Paket → Jadwal → Selesai). */
export const useBookingStore = defineStore('booking', () => {
  // ── Detail psikolog yang dibooking ────────────────────────────────────────
  const psikolog = ref<any>(null)
  const loadingDetail = ref(false)
  const detailError = ref('')
  const loadedForSlug = ref('')

  // ── Paket (langkah 1) ─────────────────────────────────────────────────────
  const consultationType = ref<ConsultationType>('video')
  const durationMinutes = ref<30 | 60 | 90>(60)
  const note = ref('')

  // ── Kategori klien (hanya informasi tarif, tidak diproses sebagai transaksi)
  const categories = ref<any[]>([])

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

  /** Dipanggil parent saat masuk/menembus slug baru — reset state wizard. */
  function startFor(slug: string) {
    if (loadedForSlug.value === slug && psikolog.value) return
    loadedForSlug.value = slug
    psikolog.value = null
    detailError.value = ''
    loadingDetail.value = true
    consultationType.value = 'video'
    durationMinutes.value = 60
    note.value = ''
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
    note,
    categories,
    bookingDate,
    slots,
    loadingSlots,
    selectedSlot,
    confirmedBooking,
    infoRate,
    startFor,
    resetSchedule,
  }
})
