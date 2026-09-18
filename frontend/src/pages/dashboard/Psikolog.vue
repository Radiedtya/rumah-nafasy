<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import {
  StarIcon,
  CheckBadgeIcon,
  AcademicCapIcon,
  VideoCameraIcon,
  ChatBubbleLeftRightIcon,
  ArrowRightIcon,
  MapPinIcon,
} from '@heroicons/vue/24/outline'
import { apiFetch } from '../../lib/api'
import { useAuthStore } from '../../stores/auth'
import RekaAutocomplete from '../../components/ui/RekaAutocomplete.vue'
import PageHeader from '../../components/dashboard/PageHeader.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseBadge from '../../components/ui/BaseBadge.vue'
import BaseAvatar from '../../components/ui/BaseAvatar.vue'
import BaseSkeleton from '../../components/ui/BaseSkeleton.vue'
import BaseEmpty from '../../components/ui/BaseEmpty.vue'
import BaseModal from '../../components/ui/BaseModal.vue'
import BaseTabs from '../../components/ui/BaseTabs.vue'

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
const bookingStep = ref<1 | 2 | 3 | 4>(1)
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

const bookingTabs = [
  { value: '1', label: 'Paket' },
  { value: '2', label: 'Pembayaran' },
  { value: '3', label: 'Jadwal' },
  { value: '4', label: 'Selesai' },
]

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
  return psikologList.value.map((p) => ({
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
  const found = psikologList.value.find((p) => p.id === item.id)
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
      `pasien/psikolog/${selectedPsikolog.value.id}/slots?date=${selectedBookingDate.value}&duration_minutes=${durMinutes}`,
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
  <div>
    <PageHeader
      title="Cari Psikolog Terverifikasi"
      description="Pilih psikolog berlisensi resmi SIP & HIMPsi sesuai kebutuhan konseling Anda."
    >
      <template #actions>
        <label class="flex items-center gap-2 text-xs text-[var(--muted)]">
          Urutkan
          <select v-model="sortBy" class="field-input w-40 py-1.5">
            <option value="rating">Rating Tertinggi</option>
            <option value="experience">Pengalaman Terlama</option>
            <option value="name">Nama (A-Z)</option>
          </select>
        </label>
      </template>
    </PageHeader>

    <!-- Search & filter -->
    <BaseCard class="mb-5">
      <div class="flex flex-col gap-3 md:flex-row md:items-center">
        <div class="md:w-2/3">
          <RekaAutocomplete
            :items="autocompleteItems"
            placeholder="Cari psikolog berdasarkan nama atau spesialisasi…"
            @select="onAutocompleteSelect"
          />
        </div>
        <div class="scrollbar-hide flex items-center gap-1.5 overflow-x-auto pb-0.5 md:w-1/3">
          <button
            type="button"
            class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
            :class="
              selectedSpecSlug === ''
                ? 'bg-[var(--accent)] text-white'
                : 'bg-[var(--muted)]/8 text-[var(--muted)] hover:text-[var(--text)]'
            "
            @click="selectedSpecSlug = ''"
          >
            Semua
          </button>
          <button
            v-for="spec in specializations"
            :key="spec.slug"
            type="button"
            class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
            :class="
              selectedSpecSlug === spec.slug
                ? 'bg-[var(--accent)] text-white'
                : 'bg-[var(--muted)]/8 text-[var(--muted)] hover:text-[var(--text)]'
            "
            @click="selectedSpecSlug = spec.slug"
          >
            {{ spec.name }}
          </button>
        </div>
      </div>
    </BaseCard>

    <!-- Directory Grid -->
    <div v-if="loading" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <BaseSkeleton v-for="i in 6" :key="i" class="h-56" />
    </div>

    <BaseCard v-else-if="psikologList.length === 0" :padded="false">
      <BaseEmpty
        icon="🔍"
        title="Tidak ada psikolog yang cocok"
        description="Coba sesuaikan kata kunci atau filter spesialisasi."
      />
    </BaseCard>

    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <BaseCard
        v-for="psikolog in psikologList"
        :key="psikolog.id"
        class="group flex flex-col justify-between transition-shadow duration-150 hover:shadow-sm"
      >
        <div class="space-y-3">
          <!-- Top info -->
          <div class="flex items-start gap-3">
            <BaseAvatar :name="psikolog.name" :src="psikolog.avatar" size="lg" />
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-1.5">
                <BaseBadge tone="success">
                  <CheckBadgeIcon class="h-3 w-3" />
                  SIP
                </BaseBadge>
                <BaseBadge>{{ psikolog.experience_years }} thn</BaseBadge>
              </div>
              <h3 class="mt-1.5 truncate text-sm font-semibold text-[var(--text)]">
                {{ psikolog.name }}
              </h3>
              <p class="truncate text-xs text-[var(--accent)]">
                {{ psikolog.specialization || 'Psikolog Klinis' }}
              </p>
            </div>
          </div>

          <!-- Bio -->
          <p class="line-clamp-2 text-xs leading-relaxed text-[var(--muted)]">
            {{
              psikolog.bio ||
              'Praktisi psikolog berpengalaman mendampingi klien mengatasi stres, kecemasan, dan peningkatan kualitas hidup.'
            }}
          </p>

          <!-- Education & workplace -->
          <div class="space-y-1 border-t border-[var(--line)] pt-2.5 text-[11px] text-[var(--muted)]">
            <p v-if="psikolog.education" class="flex items-center gap-1.5 truncate">
              <AcademicCapIcon class="h-3.5 w-3.5 shrink-0" />
              {{ psikolog.education }}
            </p>
            <p v-if="psikolog.workplace" class="flex items-center gap-1.5 truncate">
              <MapPinIcon class="h-3.5 w-3.5 shrink-0" />
              {{ psikolog.workplace }}
            </p>
          </div>
        </div>

        <!-- Footer / Action -->
        <div class="mt-4 flex items-center justify-between gap-3 border-t border-[var(--line)] pt-3">
          <div class="flex items-center gap-1 text-xs">
            <StarIcon class="h-4 w-4 fill-amber-400 text-amber-400" />
            <strong class="font-semibold tabular-nums text-[var(--text)]">
              {{ psikolog.rating_avg ? psikolog.rating_avg.toFixed(1) : '5.0' }}
            </strong>
            <span class="text-[11px] text-[var(--muted)]">({{ psikolog.total_consultations || 0 }} sesi)</span>
          </div>

          <BaseButton size="sm" @click="openBooking(psikolog)">
            Konsultasi
            <ArrowRightIcon class="h-3 w-3" />
          </BaseButton>
        </div>
      </BaseCard>
    </div>

    <!-- ================= BOOKING MODAL ================= -->
    <BaseModal
      v-model:open="bookingModalOpen"
      max-width="max-w-lg"
      :title="`Konsultasi dengan ${selectedPsikolog?.name || ''}`"
    >
      <template #description>
        <span class="font-medium text-[var(--accent)]">Langkah {{ bookingStep }} dari 4</span>
      </template>

      <div class="space-y-4">
        <BaseTabs :model-value="String(bookingStep)" :tabs="bookingTabs" />

        <div v-if="bookingError" class="rounded-lg bg-rose-500/10 px-3 py-2 text-xs text-rose-600 dark:text-rose-400">
          {{ bookingError }}
        </div>

        <!-- ================= STEP 1: PILIH KATEGORI & DURASI ================= -->
        <div v-if="bookingStep === 1" class="space-y-4">
          <div>
            <label class="field-label">1. Pilih Kategori Klien</label>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
              <button
                v-for="cat in categories"
                :key="cat.id"
                type="button"
                class="rounded-lg border p-2.5 text-left text-xs transition-all"
                :class="
                  selectedCategory?.id === cat.id
                    ? 'border-[var(--accent)] bg-[var(--accent)]/10 font-semibold text-[var(--accent)]'
                    : 'border-[var(--line)] text-[var(--muted)] hover:bg-[var(--muted)]/8'
                "
                @click="selectedCategory = cat"
              >
                <p class="font-semibold">{{ cat.name }}</p>
                <p class="mt-0.5 text-[10px] opacity-70">{{ formatRupiah(cat.base_price) }}/60m</p>
              </button>
            </div>
          </div>

          <div>
            <label class="field-label">2. Pilih Durasi Sesi</label>
            <div class="grid grid-cols-3 gap-2">
              <button
                v-for="dur in durations"
                :key="dur.id"
                type="button"
                class="rounded-lg border p-2.5 text-center text-xs transition-all"
                :class="
                  selectedDuration?.id === dur.id
                    ? 'border-[var(--accent)] bg-[var(--accent)]/10 font-semibold text-[var(--accent)]'
                    : 'border-[var(--line)] text-[var(--muted)] hover:bg-[var(--muted)]/8'
                "
                @click="selectedDuration = dur"
              >
                <p class="font-semibold">{{ dur.name }}</p>
                <p class="mt-0.5 text-[10px] opacity-70">{{ dur.multiplier }}x harga</p>
              </button>
            </div>
          </div>

          <div>
            <label class="field-label">3. Media Konsultasi</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                class="flex items-center justify-center gap-2 rounded-lg border p-2.5 text-xs font-medium transition-all"
                :class="
                  consultationType === 'video'
                    ? 'border-[var(--accent)] bg-[var(--accent)]/10 text-[var(--accent)]'
                    : 'border-[var(--line)] text-[var(--muted)] hover:bg-[var(--muted)]/8'
                "
                @click="consultationType = 'video'"
              >
                <VideoCameraIcon class="h-4 w-4" />
                Video Call (Jitsi)
              </button>
              <button
                type="button"
                class="flex items-center justify-center gap-2 rounded-lg border p-2.5 text-xs font-medium transition-all"
                :class="
                  consultationType === 'chat'
                    ? 'border-[var(--accent)] bg-[var(--accent)]/10 text-[var(--accent)]'
                    : 'border-[var(--line)] text-[var(--muted)] hover:bg-[var(--muted)]/8'
                "
                @click="consultationType = 'chat'"
              >
                <ChatBubbleLeftRightIcon class="h-4 w-4" />
                Chat Teks
              </button>
            </div>
          </div>

          <!-- Price summary -->
          <div class="flex items-center justify-between rounded-xl border border-[var(--line)] bg-[var(--muted)]/5 p-4">
            <div>
              <p class="text-[11px] text-[var(--muted)]">Estimasi Total Biaya</p>
              <p class="text-xl font-semibold tabular-nums text-[var(--accent)]">
                {{ formatRupiah(calculatedPrice) }}
              </p>
            </div>
            <BaseButton size="md" :disabled="isProcessingOrder" @click="proceedToPayment">
              {{ isProcessingOrder ? 'Membuat Order…' : 'Lanjut ke Pembayaran' }}
              <ArrowRightIcon class="h-3.5 w-3.5" />
            </BaseButton>
          </div>
        </div>

        <!-- ================= STEP 2: PEMBAYARAN ================= -->
        <div v-if="bookingStep === 2" class="space-y-4">
          <div class="space-y-2.5 rounded-xl border border-[var(--line)] bg-[var(--muted)]/5 p-4 text-xs">
            <div class="flex items-center justify-between">
              <span class="text-[var(--muted)]">Nomor Order</span>
              <strong class="font-mono text-[var(--text)]">{{ createdOrder?.order_number }}</strong>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-[var(--muted)]">Total Tagihan</span>
              <strong class="tabular-nums text-[var(--accent)]">{{ formatRupiah(createdOrder?.calculated_price) }}</strong>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-[var(--muted)]">Status Pembayaran</span>
              <BaseBadge tone="warning">Pending</BaseBadge>
            </div>
          </div>

          <p class="text-xs leading-relaxed text-[var(--muted)]">
            Di lingkungan development ini, Anda dapat langsung melakukan
            <strong class="text-[var(--text)]">simulasi pembayaran berhasil</strong> yang terhubung ke webhook backend Laravel.
          </p>

          <BaseButton variant="success" size="md" class="w-full" :disabled="isProcessingOrder" @click="confirmPaymentMock">
            {{ isProcessingOrder ? 'Menyinkronkan…' : 'Konfirmasi & Selesaikan Pembayaran (Simulasi)' }}
          </BaseButton>
        </div>

        <!-- ================= STEP 3: PILIH SLOT WAKTU ================= -->
        <div v-if="bookingStep === 3" class="space-y-4">
          <div>
            <label class="field-label">Pilih Tanggal Sesi</label>
            <input v-model="selectedBookingDate" type="date" class="field-input" @change="fetchSlots" />
          </div>

          <div>
            <label class="field-label">Slot Waktu yang Tersedia</label>
            <div v-if="loadingSlots" class="py-6 text-center text-xs text-[var(--muted)]">
              Memeriksa ketersediaan slot psikolog…
            </div>
            <div
              v-else-if="availableSlots.length === 0"
              class="rounded-lg bg-amber-500/10 px-3 py-2.5 text-center text-xs text-amber-600 dark:text-amber-400"
            >
              Tidak ada slot tersedia di tanggal ini. Silakan pilih hari lain.
            </div>
            <div v-else class="grid max-h-48 grid-cols-2 gap-2 overflow-y-auto sm:grid-cols-3">
              <button
                v-for="slot in availableSlots"
                :key="slot.start_time"
                type="button"
                class="rounded-lg border p-2.5 text-center text-xs tabular-nums transition-all"
                :class="
                  selectedSlot?.start_time === slot.start_time
                    ? 'border-[var(--accent)] bg-[var(--accent)]/10 font-semibold text-[var(--accent)]'
                    : 'border-[var(--line)] text-[var(--muted)] hover:bg-[var(--muted)]/8'
                "
                @click="selectedSlot = slot"
              >
                <p class="font-semibold">{{ slot.start_time }}</p>
                <p class="mt-0.5 text-[10px] opacity-60">s/d {{ slot.end_time }}</p>
              </button>
            </div>
          </div>

          <BaseButton
            size="md"
            class="w-full"
            :disabled="isProcessingOrder || !selectedSlot"
            @click="confirmSchedule"
          >
            {{ isProcessingOrder ? 'Memproses Reservasi…' : 'Konfirmasi Jadwal Konsultasi' }}
          </BaseButton>
        </div>

        <!-- ================= STEP 4: SELESAI ================= -->
        <div v-if="bookingStep === 4" class="space-y-4 py-4 text-center">
          <div
            class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500/10 text-2xl text-emerald-600 dark:text-emerald-400"
          >
            ✓
          </div>
          <div>
            <h4 class="text-sm font-semibold text-[var(--text)]">Reservasi Berhasil Dikonfirmasi</h4>
            <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-[var(--muted)]">
              Sesi Anda telah dijadwalkan pada
              <strong class="text-[var(--text)]">{{ confirmedBooking?.booking_date }}</strong>
              pukul <strong class="tabular-nums text-[var(--accent)]">{{ confirmedBooking?.start_time }}</strong> WIB.
            </p>
          </div>

          <div class="flex items-center justify-center gap-3 pt-2">
            <RouterLink to="/dashboard/sesi" @click="bookingModalOpen = false">
              <BaseButton variant="secondary" size="sm">Lihat di Sesi Saya</BaseButton>
            </RouterLink>

            <a
              v-if="confirmedBooking?.room_id"
              :href="`https://meet.jit.si/${confirmedBooking.room_id}`"
              target="_blank"
              rel="noopener noreferrer"
            >
              <BaseButton size="sm">Uji Ruang Video Call</BaseButton>
            </a>
          </div>
        </div>
      </div>
    </BaseModal>
  </div>
</template>
