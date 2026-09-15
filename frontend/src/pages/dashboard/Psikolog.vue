<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import {
  StarIcon,
  CheckBadgeIcon,
  AcademicCapIcon,
  VideoCameraIcon,
  ChatBubbleLeftRightIcon,
  XMarkIcon,
  ArrowRightIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/20/solid'
import { apiFetch } from '../../lib/api'
import { useAuthStore } from '../../stores/auth'
import RekaAutocomplete from '../../components/ui/RekaAutocomplete.vue'

const auth = useAuthStore()

// State
const loading = ref(true)
const psikologList = ref<any[]>([])
const specializations = ref<any[]>([])
const categories = ref<any[]>([])
const durations = ref<any[]>([])

// Filter & Search
const selectedSpecSlug = ref<string>('')
const sortBy = ref('rating')

// Booking modal state
const bookingModalOpen = ref(false)
const selectedPsikolog = ref<any>(null)
const bookingStep = ref<1 | 2 | 3 | 4>(1) // 1: Select type/duration, 2: Payment, 3: Select slot, 4: Success
const selectedCategory = ref<any>(null)
const selectedDuration = ref<any>(null)
const consultationType = ref<'video' | 'chat'>('video')

const isProcessingOrder = ref(false)
const createdOrder = ref<any>(null)
const paymentData = ref<any>(null)

// Slot booking
const selectedBookingDate = ref<string>('')
const availableSlots = ref<any[]>([])
const selectedSlot = ref<any>(null)
const loadingSlots = ref(false)
const confirmedBooking = ref<any>(null)
const bookingError = ref('')

// Load initial data
async function loadData() {
  loading.value = true
  try {
    const [specRes, catRes, durRes] = await Promise.all([
      apiFetch('public/specializations'),
      apiFetch('public/categories'),
      apiFetch('public/durations'),
    ])

    specializations.value = specRes.data || []
    categories.value = catRes.data || []
    durations.value = durRes.data || []

    if (categories.value.length > 0) selectedCategory.value = categories.value[1] || categories.value[0]
    if (durations.value.length > 0) selectedDuration.value = durations.value[1] || durations.value[0]

    await fetchPsikolog()
  } catch (e) {
    console.error('Failed loading public catalog', e)
  } finally {
    loading.value = false
  }
}

async function fetchPsikolog() {
  let url = 'public/psikolog?per_page=20'
  if (selectedSpecSlug.value) {
    url += `&specialization=${encodeURIComponent(selectedSpecSlug.value)}`
  }
  if (sortBy.value) {
    url += `&sort=${encodeURIComponent(sortBy.value)}`
  }

  try {
    const res = await apiFetch(url)
    psikologList.value = res.data?.data || res.data || []
  } catch (e) {
    console.error('Failed fetching psikolog list', e)
  }
}

onMounted(() => {
  loadData()
  const today = new Date()
  today.setDate(today.getDate() + 1)
  selectedBookingDate.value = today.toISOString().split('T')[0]
})

watch([selectedSpecSlug, sortBy], () => {
  fetchPsikolog()
})

// Autocomplete items
const autocompleteItems = computed(() => {
  return psikologList.value.map(p => ({
    id: p.id,
    label: p.name,
    sub: `${p.specialization || 'Psikolog'} · Pengalaman ${p.experience_years} tahun`,
    meta: {
      avatar: p.avatar,
      icon: '🧠',
    },
  }))
})

function onAutocompleteSelect(item: any) {
  const found = psikologList.value.find(p => p.id === item.id)
  if (found) {
    openBooking(found)
  }
}

// Price calculation formula
const calculatedPrice = computed(() => {
  if (!selectedCategory.value || !selectedDuration.value) return 0
  const rate = selectedPsikolog.value?.custom_rate || selectedCategory.value.base_price
  const multiplier = Number(selectedDuration.value.multiplier || 1)
  return Math.round(rate * multiplier)
})

function formatRupiah(num: number) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num)
}

function openBooking(psikolog: any) {
  selectedPsikolog.value = psikolog
  bookingStep.value = 1
  bookingError.value = ''
  bookingModalOpen.value = true
}

// Step 1 -> Step 2: Create Order & Payment
async function proceedToPayment() {
  if (!auth.isAuthenticated) {
    // If not logged in, auto login demo pasien
    await auth.login('rina@example.com', 'password').catch(() => {})
  }

  isProcessingOrder.value = true
  bookingError.value = ''

  try {
    // 1. Create order
    const orderRes = await apiFetch('pasien/orders', {
      method: 'POST',
      body: JSON.stringify({
        psikolog_id: selectedPsikolog.value.id,
        category_id: selectedCategory.value.id,
        duration_id: selectedDuration.value.id,
        consultation_type: consultationType.value,
      }),
    })
    createdOrder.value = orderRes.data

    // 2. Create payment
    const paymentRes = await apiFetch(`pasien/orders/${createdOrder.value.id}/payment`, {
      method: 'POST',
    })
    paymentData.value = paymentRes.data
    bookingStep.value = 2
  } catch (err: any) {
    bookingError.value = err.message || 'Gagal memproses order'
  } finally {
    isProcessingOrder.value = false
  }
}

// Step 2 -> Step 3: Simulate Payment Success via mock webhook
async function confirmPaymentMock() {
  isProcessingOrder.value = true
  bookingError.value = ''
  try {
    // Call mock webhook
    await apiFetch(`webhooks/midtrans?mock=1&order_id=${createdOrder.value.order_number}`)
    bookingStep.value = 3
    await fetchSlots()
  } catch (err: any) {
    bookingError.value = err.message || 'Gagal konfirmasi pembayaran'
  } finally {
    isProcessingOrder.value = false
  }
}

// Fetch Slots
async function fetchSlots() {
  if (!selectedPsikolog.value || !selectedBookingDate.value) return
  loadingSlots.value = true
  availableSlots.value = []
  selectedSlot.value = null
  bookingError.value = ''

  try {
    const durMinutes = selectedDuration.value?.minutes || 60
    const res = await apiFetch(
      `pasien/psikolog/${selectedPsikolog.value.id}/slots?date=${selectedBookingDate.value}&duration_minutes=${durMinutes}`
    )
    availableSlots.value = res.data?.available_slots || []
    if (availableSlots.value.length > 0) {
      selectedSlot.value = availableSlots.value[0]
    }
  } catch (err: any) {
    bookingError.value = err.message || 'Gagal memuat jadwal'
  } finally {
    loadingSlots.value = false
  }
}

// Step 3 -> Step 4: Confirm Schedule
async function confirmSchedule() {
  if (!selectedSlot.value) {
    bookingError.value = 'Silakan pilih slot waktu terlebih dahulu'
    return
  }

  isProcessingOrder.value = true
  bookingError.value = ''

  try {
    const res = await apiFetch(`pasien/orders/${createdOrder.value.id}/schedule`, {
      method: 'POST',
      body: JSON.stringify({
        booking_date: selectedBookingDate.value,
        start_time: selectedSlot.value.start_time,
      }),
    })
    confirmedBooking.value = res.data
    bookingStep.value = 4
  } catch (err: any) {
    bookingError.value = err.message || 'Gagal memilih jadwal'
  } finally {
    isProcessingOrder.value = false
  }
}
</script>

<template>
  <div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="font-display text-2xl font-bold text-neutral-900 dark:text-neutral-100">
          Cari Psikolog Terverifikasi
        </h2>
        <p class="text-neutral-500 text-sm mt-0.5">
          Pilih psikolog berlisensi resmi SIP & HIMPsi sesuai kebutuhan konseling Anda.
        </p>
      </div>

      <!-- Sort -->
      <div class="flex items-center gap-2 self-start sm:self-auto">
        <label class="text-xs text-neutral-500 font-medium">Urutkan:</label>
        <select
          v-model="sortBy"
          class="text-xs bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl px-3 py-2 text-neutral-800 dark:text-neutral-200 outline-none"
        >
          <option value="rating">Rating Tertinggi</option>
          <option value="experience">Pengalaman Terlama</option>
          <option value="name">Nama (A-Z)</option>
        </select>
      </div>
    </div>

    <!-- Search with Reka UI Autocomplete -->
    <div class="bg-white dark:bg-neutral-900 border border-neutral-200/80 dark:border-neutral-800 rounded-2xl p-4 shadow-xs">
      <div class="flex flex-col md:flex-row items-center gap-4">
        <!-- Autocomplete Input -->
        <div class="w-full md:w-2/3">
          <RekaAutocomplete
            :items="autocompleteItems"
            placeholder="Cari psikolog dengan Reka UI Autocomplete (nama / spesialisasi)..."
            @select="onAutocompleteSelect"
          />
        </div>

        <!-- Spesialisasi quick buttons -->
        <div class="w-full md:w-1/3 flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
          <button
            type="button"
            class="px-3 py-2 rounded-xl shrink-0 font-medium transition-colors"
            :class="selectedSpecSlug === '' ? 'bg-rose-500 text-white shadow-xs' : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300 hover:bg-neutral-200'"
            @click="selectedSpecSlug = ''"
          >
            Semua
          </button>
          <button
            v-for="spec in specializations"
            :key="spec.slug"
            type="button"
            class="px-3 py-2 rounded-xl shrink-0 font-medium transition-colors"
            :class="selectedSpecSlug === spec.slug ? 'bg-rose-500 text-white shadow-xs' : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300 hover:bg-neutral-200'"
            @click="selectedSpecSlug = spec.slug"
          >
            {{ spec.name }}
          </button>
        </div>
      </div>
    </div>

    <!-- Directory Grid -->
    <div v-if="loading" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <div v-for="i in 6" :key="i" class="h-64 bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 animate-pulse" />
    </div>

    <div v-else-if="psikologList.length === 0" class="bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 p-12 text-center text-neutral-400">
      <div class="text-4xl mb-2">🔍</div>
      <p class="font-medium text-neutral-700 dark:text-neutral-200">Tidak ada psikolog yang cocok</p>
      <p class="text-xs text-neutral-500 mt-1">Coba sesuaikan kata kunci atau filter spesialisasi.</p>
    </div>

    <div v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="psikolog in psikologList"
        :key="psikolog.id"
        class="bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200/80 dark:border-neutral-800 p-5 shadow-xs hover:shadow-md transition-all flex flex-col justify-between group"
      >
        <div class="space-y-3">
          <!-- Top info -->
          <div class="flex items-start gap-3">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-rose-400 to-orange-400 text-white font-display text-xl font-bold flex items-center justify-center shrink-0 shadow-sm">
              {{ psikolog.name.charAt(0) }}
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-1.5 flex-wrap">
                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                  <CheckBadgeIcon class="w-3 h-3" />
                  SIP: {{ psikolog.license_no || 'Aktif' }}
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-md bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400">
                  {{ psikolog.experience_years }} thn pengalaman
                </span>
              </div>
              <h3 class="font-display font-bold text-neutral-900 dark:text-neutral-100 text-sm mt-1 truncate group-hover:text-rose-600 transition-colors">
                {{ psikolog.name }}
              </h3>
              <p class="text-xs text-rose-500 font-medium">
                {{ psikolog.specialization || 'Psikolog Klinis' }}
              </p>
            </div>
          </div>

          <!-- Bio -->
          <p class="text-xs text-neutral-600 dark:text-neutral-400 line-clamp-2 leading-relaxed">
            {{ psikolog.bio || 'Praktisi psikolog berpengalaman mendampingi klien mengatasi stres, kecemasan, dan peningkatan kualitas hidup.' }}
          </p>

          <!-- Education & Workplace -->
          <div class="text-[11px] text-neutral-500 dark:text-neutral-400 space-y-0.5 pt-2 border-t border-neutral-100 dark:border-neutral-800">
            <p v-if="psikolog.education" class="truncate flex items-center gap-1">
              <AcademicCapIcon class="w-3.5 h-3.5 text-neutral-400 shrink-0" />
              {{ psikolog.education }}
            </p>
            <p v-if="psikolog.workplace" class="truncate">
              📍 {{ psikolog.workplace }}
            </p>
          </div>
        </div>

        <!-- Footer / Action -->
        <div class="mt-4 pt-3 border-t border-neutral-100 dark:border-neutral-800 flex items-center justify-between gap-3">
          <div class="flex items-center gap-1 text-xs">
            <StarIcon class="w-4 h-4 text-amber-400 fill-amber-400" />
            <strong class="font-bold text-neutral-900 dark:text-neutral-100">
              {{ psikolog.rating_avg ? psikolog.rating_avg.toFixed(1) : '5.0' }}
            </strong>
            <span class="text-[11px] text-neutral-400">({{ psikolog.total_consultations || 0 }} sesi)</span>
          </div>

          <button
            type="button"
            class="px-4 py-2 rounded-xl bg-rose-500 text-white text-xs font-semibold hover:bg-rose-600 active:scale-95 transition-all shadow-xs"
            @click="openBooking(psikolog)"
          >
            Konsultasi Sekarang
          </button>
        </div>
      </div>
    </div>

    <!-- ================= BOOKING MODAL ================= -->
    <div
      v-if="bookingModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
    >
      <div class="bg-white dark:bg-neutral-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-neutral-200 dark:border-neutral-800 space-y-5 animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
          <div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-500">
              Langkah {{ bookingStep }} dari 4
            </span>
            <h3 class="font-display text-base font-bold text-neutral-900 dark:text-neutral-100">
              Konsultasi dengan {{ selectedPsikolog?.name }}
            </h3>
          </div>
          <button
            type="button"
            class="p-1 rounded-lg text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200"
            @click="bookingModalOpen = false"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <div v-if="bookingError" class="p-3 rounded-xl bg-rose-50 text-rose-700 text-xs font-medium">
          {{ bookingError }}
        </div>

        <!-- ================= STEP 1: PILIH KATEGORI & DURASI ================= -->
        <div v-if="bookingStep === 1" class="space-y-4">
          <!-- Kategori Klien -->
          <div>
            <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-2 block">
              1. Pilih Kategori Klien
            </label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
              <button
                v-for="cat in categories"
                :key="cat.id"
                type="button"
                class="p-2.5 rounded-xl border text-left text-xs transition-all"
                :class="selectedCategory?.id === cat.id
                  ? 'border-rose-500 bg-rose-50/50 dark:bg-rose-950/20 text-rose-600 font-semibold'
                  : 'border-neutral-200 dark:border-neutral-800 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50'"
                @click="selectedCategory = cat"
              >
                <p class="font-bold">{{ cat.name }}</p>
                <p class="text-[10px] text-neutral-500">{{ formatRupiah(cat.base_price) }}/60m</p>
              </button>
            </div>
          </div>

          <!-- Durasi -->
          <div>
            <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-2 block">
              2. Pilih Durasi Sesi
            </label>
            <div class="grid grid-cols-3 gap-2">
              <button
                v-for="dur in durations"
                :key="dur.id"
                type="button"
                class="p-2.5 rounded-xl border text-center text-xs transition-all"
                :class="selectedDuration?.id === dur.id
                  ? 'border-rose-500 bg-rose-50/50 dark:bg-rose-950/20 text-rose-600 font-semibold'
                  : 'border-neutral-200 dark:border-neutral-800 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50'"
                @click="selectedDuration = dur"
              >
                <p class="font-bold">{{ dur.name }}</p>
                <p class="text-[10px] text-neutral-500">{{ dur.multiplier }}x harga</p>
              </button>
            </div>
          </div>

          <!-- Tipe Konsultasi -->
          <div>
            <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-2 block">
              3. Media Konsultasi
            </label>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                class="flex items-center gap-2 p-2.5 rounded-xl border text-xs font-medium transition-all"
                :class="consultationType === 'video' ? 'border-rose-500 bg-rose-50/50 text-rose-600 font-bold' : 'border-neutral-200 text-neutral-700'"
                @click="consultationType = 'video'"
              >
                <VideoCameraIcon class="w-4 h-4" />
                Video Call (Jitsi)
              </button>
              <button
                type="button"
                class="flex items-center gap-2 p-2.5 rounded-xl border text-xs font-medium transition-all"
                :class="consultationType === 'chat' ? 'border-rose-500 bg-rose-50/50 text-rose-600 font-bold' : 'border-neutral-200 text-neutral-700'"
                @click="consultationType = 'chat'"
              >
                <ChatBubbleLeftRightIcon class="w-4 h-4" />
                Chat Teks
              </button>
            </div>
          </div>

          <!-- Price Summary Box -->
          <div class="p-4 rounded-2xl bg-neutral-50 dark:bg-neutral-800 border border-neutral-200/80 dark:border-neutral-700 flex items-center justify-between">
            <div>
              <p class="text-[11px] text-neutral-500">Estimasi Total Biaya:</p>
              <p class="font-display text-xl font-bold text-rose-600">
                {{ formatRupiah(calculatedPrice) }}
              </p>
            </div>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-rose-500 text-white text-xs font-bold hover:bg-rose-600 transition-all shadow-xs"
              :disabled="isProcessingOrder"
              @click="proceedToPayment"
            >
              <span>{{ isProcessingOrder ? 'Membuat Order...' : 'Lanjut ke Pembayaran' }}</span>
              <ArrowRightIcon class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <!-- ================= STEP 2: SIMULASI / PEMBAYARAN MIDTRANS ================= -->
        <div v-if="bookingStep === 2" class="space-y-4">
          <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900 text-xs text-neutral-800 dark:text-neutral-200 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-neutral-500">Nomor Order:</span>
              <strong class="font-mono text-neutral-900 dark:text-neutral-100">{{ createdOrder?.order_number }}</strong>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-neutral-500">Total Tagihan:</span>
              <strong class="text-rose-600 font-bold text-sm">{{ formatRupiah(createdOrder?.calculated_price) }}</strong>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-neutral-500">Status Pembayaran:</span>
              <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-700 text-[10px] font-bold uppercase">Pending</span>
            </div>
          </div>

          <div class="text-xs text-neutral-600 dark:text-neutral-400 leading-relaxed">
            <p>
              💡 Di lingkungan development ini, Anda dapat langsung melakukan <strong>Simulasi Pembayaran Berhasil</strong> yang terhubung ke webhook backend Laravel.
            </p>
          </div>

          <button
            type="button"
            class="w-full py-3 rounded-xl bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition-colors shadow-xs flex items-center justify-center gap-2"
            :disabled="isProcessingOrder"
            @click="confirmPaymentMock"
          >
            <ShieldCheckIcon class="w-4 h-4" />
            <span>{{ isProcessingOrder ? 'Menyinkronkan...' : 'Konfirmasi & Selesaikan Pembayaran (Simulasi)' }}</span>
          </button>
        </div>

        <!-- ================= STEP 3: PILIH SLOT WAKTU ================= -->
        <div v-if="bookingStep === 3" class="space-y-4">
          <div>
            <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-1.5 block">
              Pilih Tanggal Sesi:
            </label>
            <input
              v-model="selectedBookingDate"
              type="date"
              class="w-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl px-3 py-2 text-xs text-neutral-900 dark:text-neutral-100 outline-none"
              @change="fetchSlots"
            />
          </div>

          <div>
            <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-1.5 block">
              Slot Waktu yang Tersedia:
            </label>

            <div v-if="loadingSlots" class="py-6 text-center text-xs text-neutral-400">
              Memeriksa ketersediaan slot psikolog...
            </div>

            <div v-else-if="availableSlots.length === 0" class="p-4 rounded-xl bg-amber-50 text-amber-700 text-xs text-center">
              Tidak ada slot tersedia di tanggal ini. Silakan pilih hari lain.
            </div>

            <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto p-1">
              <button
                v-for="slot in availableSlots"
                :key="slot.start_time"
                type="button"
                class="p-2.5 rounded-xl border text-center text-xs transition-all"
                :class="selectedSlot?.start_time === slot.start_time
                  ? 'border-rose-500 bg-rose-50 text-rose-600 font-bold ring-2 ring-rose-500/20'
                  : 'border-neutral-200 dark:border-neutral-800 hover:bg-neutral-50'"
                @click="selectedSlot = slot"
              >
                <p class="font-semibold">{{ slot.start_time }}</p>
                <p class="text-[10px] text-neutral-400">s/d {{ slot.end_time }}</p>
              </button>
            </div>
          </div>

          <button
            type="button"
            class="w-full py-3 rounded-xl bg-rose-500 text-white font-bold text-xs hover:bg-rose-600 transition-colors shadow-xs"
            :disabled="isProcessingOrder || !selectedSlot"
            @click="confirmSchedule"
          >
            {{ isProcessingOrder ? 'Memproses Reservasi...' : 'Konfirmasi Jadwal Konsultasi' }}
          </button>
        </div>

        <!-- ================= STEP 4: SELESAI ================= -->
        <div v-if="bookingStep === 4" class="space-y-4 text-center py-4">
          <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto text-2xl">
            ✓
          </div>
          <div>
            <h4 class="font-display text-lg font-bold text-neutral-900 dark:text-neutral-100">
              Reservasi Berhasil Dikonfirmasi!
            </h4>
            <p class="text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
              Sesi Anda telah dijadwalkan pada <strong class="text-neutral-900 dark:text-neutral-100">{{ confirmedBooking?.booking_date }}</strong> pukul <strong class="text-rose-600">{{ confirmedBooking?.start_time }}</strong>.
            </p>
          </div>

          <div class="flex items-center justify-center gap-3 pt-2">
            <RouterLink
              to="/dashboard/sesi"
              class="px-4 py-2.5 rounded-xl bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 text-xs font-semibold hover:bg-neutral-200"
              @click="bookingModalOpen = false"
            >
              Lihat di Sesi Saya
            </RouterLink>

            <a
              v-if="confirmedBooking?.room_id"
              :href="`https://meet.jit.si/${confirmedBooking.room_id}`"
              target="_blank"
              rel="noopener noreferrer"
              class="px-4 py-2.5 rounded-xl bg-rose-500 text-white text-xs font-semibold hover:bg-rose-600 shadow-xs"
            >
              Uji Ruang Video Call
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
