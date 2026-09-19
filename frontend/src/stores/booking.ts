import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export function formatRupiah(num: number | null | undefined) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(num ?? 0)
}

/**
 * State wizard booking 4 langkah (Paket → Pembayaran → Jadwal → Selesai).
 * Hidup di sini agar state selamat saat berpindah antar halaman booking.
 */
export const useBookingStore = defineStore('booking', () => {
  // ── Katalog publik ────────────────────────────────────────────────────────
  const categories = ref<any[]>([])
  const durations = ref<any[]>([])

  // ── Detail psikolog yang dibooking ────────────────────────────────────────
  const psikolog = ref<any>(null)
  const loadingDetail = ref(false)
  const detailError = ref('')
  const loadedForSlug = ref('')

  // ── Pilihan paket (langkah 1) ─────────────────────────────────────────────
  const selectedCategory = ref<any>(null)
  const selectedDuration = ref<any>(null)
  const consultationType = ref<'video' | 'chat'>('video')

  // ── Order & pembayaran (langkah 2) ────────────────────────────────────────
  const order = ref<any>(null)
  const payment = ref<any>(null)
  const paymentConfirmed = ref(false)
  /** URL halaman pembayaran Midtrans Snap dari endpoint resmi backend. */
  const snapUrl = ref('')
  /** true hanya jika backend melaporkan gateway belum dikonfigurasi. */
  const isMockPayment = ref(false)

  // ── Jadwal (langkah 3) ────────────────────────────────────────────────────
  const bookingDate = ref('')
  const slots = ref<any[]>([])
  const loadingSlots = ref(false)
  const selectedSlot = ref<any>(null)

  // ── Hasil (langkah 4) ─────────────────────────────────────────────────────
  const confirmedBooking = ref<any>(null)

  const calculatedPrice = computed(() => {
    if (!selectedCategory.value || !selectedDuration.value) return 0
    const rate = psikolog.value?.custom_rate || selectedCategory.value.base_price
    const multiplier = Number(selectedDuration.value.multiplier || 1)
    return Math.round(rate * multiplier)
  })

  /** Dipanggil parent saat masuk/menembus slug baru — reset state wizard. */
  function startFor(slug: string) {
    if (loadedForSlug.value === slug && psikolog.value) return
    loadedForSlug.value = slug
    psikolog.value = null
    detailError.value = ''
    loadingDetail.value = true
    consultationType.value = 'video'
    order.value = null
    payment.value = null
    paymentConfirmed.value = false
    snapUrl.value = ''
    isMockPayment.value = false
    bookingDate.value = ''
    slots.value = []
    selectedSlot.value = null
    confirmedBooking.value = null
    applyCatalogDefaults()
  }

  function applyCatalogDefaults() {
    if (categories.value.length > 0) {
      selectedCategory.value = selectedCategory.value ?? categories.value[1] ?? categories.value[0] ?? null
    }
    if (durations.value.length > 0) {
      selectedDuration.value = selectedDuration.value ?? durations.value[1] ?? durations.value[0] ?? null
    }
  }

  return {
    categories,
    durations,
    psikolog,
    loadingDetail,
    detailError,
    loadedForSlug,
    selectedCategory,
    selectedDuration,
    consultationType,
    order,
    payment,
    paymentConfirmed,
    snapUrl,
    isMockPayment,
    bookingDate,
    slots,
    loadingSlots,
    selectedSlot,
    confirmedBooking,
    calculatedPrice,
    startFor,
    applyCatalogDefaults,
  }
})
