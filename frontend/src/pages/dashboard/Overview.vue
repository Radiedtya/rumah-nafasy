<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import {
  CalendarDaysIcon,
  ClockIcon,
  StarIcon,
  VideoCameraIcon,
  CurrencyDollarIcon,
  CheckCircleIcon,
  ArrowTopRightOnSquareIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '../../stores/auth'
import { apiFetch } from '../../lib/api'

const auth = useAuthStore()

const loading = ref(true)
const pasienStats = ref({
  totalBookings: 0,
  upcomingBookings: 0,
  completedBookings: 0,
})
const upcomingSessions = ref<any[]>([])
const recentOrders = ref<any[]>([])

const psikologData = ref<{
  today_bookings: any[]
  today_bookings_count: number
  upcoming_bookings_count: number
  total_completed_consultations: number
  monthly_income: number
  total_income: number
  rating_avg: number
  total_reviews: number
  is_available: boolean
  specialization?: string
} | null>(null)

async function loadData() {
  loading.value = true
  try {
    if (auth.isPsikolog) {
      const res = await apiFetch('psikolog/dashboard')
      psikologData.value = res.data
    } else {
      // Pasien data
      const [bookingsRes, ordersRes] = await Promise.all([
        apiFetch('pasien/bookings').catch(() => ({ data: [] })),
        apiFetch('pasien/orders').catch(() => ({ data: [] })),
      ])

      const bookings = bookingsRes.data?.data || bookingsRes.data || []
      const orders = ordersRes.data?.data || ordersRes.data || []

      pasienStats.value = {
        totalBookings: bookings.length,
        upcomingBookings: bookings.filter((b: any) => b.status === 'confirmed').length,
        completedBookings: bookings.filter((b: any) => b.status === 'completed').length,
      }

      upcomingSessions.value = bookings
        .filter((b: any) => b.status === 'confirmed' || b.status === 'in_progress')
        .slice(0, 3)

      recentOrders.value = orders.slice(0, 4)
    }
  } catch (err) {
    console.error('Failed loading dashboard overview', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})

watch(
  () => auth.user?.id,
  () => {
    loadData()
  }
)

function formatRupiah(amount: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(amount)
}
</script>

<template>
  <div class="max-w-6xl mx-auto space-y-6">
    <!-- Greeting Banner -->
    <div class="bg-gradient-to-r from-rose-500/10 via-orange-500/10 to-rose-500/5 border border-rose-200/60 dark:border-rose-900/40 rounded-3xl p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 shadow-xs">
      <div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 text-xs font-semibold uppercase tracking-wider mb-2">
          {{ auth.isPsikolog ? 'Panel Praktik Psikolog' : 'Kesehatan Mental Terintegrasi' }}
        </span>
        <h2 class="font-display text-2xl sm:text-3xl font-bold text-neutral-900 dark:text-neutral-50 tracking-tight">
          Halo, {{ auth.user?.name || 'Kawan' }} 👋
        </h2>
        <p class="text-neutral-600 dark:text-neutral-400 text-sm mt-1.5 max-w-xl leading-relaxed">
          {{ auth.isPsikolog
            ? 'Kelola jadwal praktek, tangani sesi konsultasi secara aman, dan akses rekam medis pasien terenkripsi.'
            : 'Jadwalkan konsultasi psikologi online dengan psikolog berlisensi resmi SIP & HIMPsi.'
          }}
        </p>
      </div>

      <div class="shrink-0 flex items-center gap-3">
        <RouterLink
          v-if="!auth.isPsikolog"
          to="/dashboard/psikolog"
          class="inline-flex items-center justify-center px-5 py-3 rounded-2xl bg-rose-500 text-white text-sm font-semibold hover:bg-rose-600 active:scale-95 transition-all shadow-md shadow-rose-500/20"
        >
          + Cari & Booking Psikolog
        </RouterLink>

        <RouterLink
          v-else
          to="/dashboard/jadwal"
          class="inline-flex items-center justify-center px-5 py-3 rounded-2xl bg-rose-500 text-white text-sm font-semibold hover:bg-rose-600 active:scale-95 transition-all shadow-md shadow-rose-500/20"
        >
          ⚙️ Kelola Jadwal Praktek
        </RouterLink>
      </div>
    </div>

    <!-- ================= PASIEN OVERVIEW ================= -->
    <div v-if="!auth.isPsikolog" class="space-y-6">
      <!-- Stats grid -->
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 p-5 shadow-xs">
          <div class="w-10 h-10 rounded-xl bg-sky-100 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 flex items-center justify-center mb-3">
            <CalendarDaysIcon class="w-5 h-5" />
          </div>
          <p class="text-xs text-neutral-500 dark:text-neutral-400">Total Sesi Dibuat</p>
          <p class="font-display text-2xl font-bold text-neutral-900 dark:text-neutral-100 mt-1">
            {{ pasienStats.totalBookings }}
          </p>
        </div>

        <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 p-5 shadow-xs">
          <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-3">
            <ClockIcon class="w-5 h-5" />
          </div>
          <p class="text-xs text-neutral-500 dark:text-neutral-400">Sesi Mendatang</p>
          <p class="font-display text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
            {{ pasienStats.upcomingBookings }}
          </p>
        </div>

        <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 p-5 shadow-xs col-span-2 sm:col-span-1">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-3">
            <CheckCircleIcon class="w-5 h-5" />
          </div>
          <p class="text-xs text-neutral-500 dark:text-neutral-400">Selesai Berkonsultasi</p>
          <p class="font-display text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
            {{ pasienStats.completedBookings }}
          </p>
        </div>
      </div>

      <!-- Sesi Mendatang & Aktivitas Terbaru -->
      <div class="grid md:grid-cols-2 gap-6">
        <!-- Sesi Mendatang -->
        <div class="bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 p-6 shadow-xs flex flex-col">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="font-display font-semibold text-neutral-900 dark:text-neutral-100 text-base">
                Sesi Konsultasi Terjadwal
              </h3>
              <p class="text-xs text-neutral-500">Jadwal konsultasi aktif Anda</p>
            </div>
            <RouterLink to="/dashboard/sesi" class="text-xs text-rose-500 hover:text-rose-600 font-semibold">
              Lihat Semua →
            </RouterLink>
          </div>

          <div v-if="upcomingSessions.length === 0" class="my-auto py-8 text-center text-neutral-400 text-sm">
            <div class="text-3xl mb-2">🗓️</div>
            <p class="font-medium text-neutral-600 dark:text-neutral-300">Belum ada sesi aktif</p>
            <p class="text-xs text-neutral-500 mt-1">Pilih psikolog favorit Anda dan jadwalkan sesi sekarang.</p>
            <RouterLink
              to="/dashboard/psikolog"
              class="inline-flex mt-4 text-xs font-semibold px-4 py-2 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors"
            >
              Cari Psikolog
            </RouterLink>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="sesi in upcomingSessions"
              :key="sesi.id"
              class="p-4 rounded-2xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-200/60 dark:border-neutral-800 flex flex-col gap-3"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-gradient-to-br from-rose-300 to-orange-300 text-white flex items-center justify-center font-bold text-sm shrink-0">
                    {{ sesi.psikolog?.name?.charAt(0) || 'P' }}
                  </div>
                  <div>
                    <h4 class="text-xs font-bold text-neutral-900 dark:text-neutral-100">
                      {{ sesi.psikolog?.name }}
                    </h4>
                    <p class="text-[11px] text-neutral-500">
                      {{ sesi.psikolog?.specialization || 'Psikolog Terverifikasi' }}
                    </p>
                  </div>
                </div>

                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider"
                  :class="sesi.status === 'confirmed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                >
                  {{ sesi.status }}
                </span>
              </div>

              <div class="flex items-center gap-2 text-xs text-neutral-600 dark:text-neutral-300 flex-wrap pt-2 border-t border-neutral-200/60 dark:border-neutral-700">
                <span class="inline-flex items-center gap-1 font-medium text-neutral-900 dark:text-neutral-100">
                  📅 {{ sesi.booking_date }}
                </span>
                <span>•</span>
                <span class="font-medium text-rose-600 dark:text-rose-400">
                  ⏰ {{ sesi.start_time }} - {{ sesi.end_time }}
                </span>
              </div>

              <div v-if="sesi.room_id" class="pt-1">
                <a
                  :href="`https://meet.jit.si/${sesi.room_id}`"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500 text-white text-xs font-semibold hover:bg-emerald-600 transition-colors w-full justify-center"
                >
                  <VideoCameraIcon class="w-3.5 h-3.5" />
                  Masuk Ruang Konsultasi Jitsi
                  <ArrowTopRightOnSquareIcon class="w-3 h-3 ml-0.5" />
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Riwayat Order & Aktivitas -->
        <div class="bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 p-6 shadow-xs flex flex-col">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="font-display font-semibold text-neutral-900 dark:text-neutral-100 text-base">
                Riwayat Order Terbaru
              </h3>
              <p class="text-xs text-neutral-500">Transaksi & pesanan Anda</p>
            </div>
          </div>

          <div v-if="recentOrders.length === 0" class="my-auto py-8 text-center text-neutral-400 text-sm">
            <p class="text-2xl mb-1">💳</p>
            <p class="text-xs text-neutral-500">Belum ada transaksi pemesanan.</p>
          </div>

          <div v-else class="space-y-2.5">
            <div
              v-for="order in recentOrders"
              :key="order.id"
              class="p-3 rounded-2xl bg-neutral-50 dark:bg-neutral-800/40 border border-neutral-100 dark:border-neutral-800 flex items-center justify-between text-xs"
            >
              <div>
                <p class="font-semibold text-neutral-900 dark:text-neutral-100">
                  {{ order.order_number }}
                </p>
                <p class="text-[11px] text-neutral-500">
                  {{ order.psikolog?.name }} · {{ order.category?.name }} ({{ order.duration?.minutes }}m)
                </p>
              </div>

              <div class="text-right">
                <p class="font-bold text-neutral-900 dark:text-neutral-100">
                  {{ formatRupiah(order.calculated_price) }}
                </p>
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full capitalize"
                  :class="{
                    'bg-emerald-100 text-emerald-700': order.status === 'paid' || order.status === 'completed' || order.status === 'scheduled',
                    'bg-amber-100 text-amber-700': order.status === 'pending_payment',
                    'bg-rose-100 text-rose-700': order.status === 'cancelled',
                  }"
                >
                  {{ order.status }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ================= PSIKOLOG OVERVIEW ================= -->
    <div v-else class="space-y-6">
      <!-- Psikolog Stats Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 p-5 shadow-xs">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 flex items-center justify-center mb-3">
            <CurrencyDollarIcon class="w-5 h-5" />
          </div>
          <p class="text-xs text-neutral-500">Pendapatan Bulan Ini</p>
          <p class="font-display text-xl font-bold text-emerald-600 mt-1">
            {{ formatRupiah(psikologData?.monthly_income || 0) }}
          </p>
        </div>

        <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 p-5 shadow-xs">
          <div class="w-10 h-10 rounded-xl bg-sky-100 dark:bg-sky-950/50 text-sky-600 flex items-center justify-center mb-3">
            <CalendarDaysIcon class="w-5 h-5" />
          </div>
          <p class="text-xs text-neutral-500">Sesi Hari Ini</p>
          <p class="font-display text-2xl font-bold text-neutral-900 dark:text-neutral-100 mt-1">
            {{ psikologData?.today_bookings_count ?? 0 }}
          </p>
        </div>

        <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 p-5 shadow-xs">
          <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950/50 text-amber-600 flex items-center justify-center mb-3">
            <ClockIcon class="w-5 h-5" />
          </div>
          <p class="text-xs text-neutral-500">Sesi Mendatang</p>
          <p class="font-display text-2xl font-bold text-amber-600 mt-1">
            {{ psikologData?.upcoming_bookings_count ?? 0 }}
          </p>
        </div>

        <div class="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 p-5 shadow-xs">
          <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-950/50 text-purple-600 flex items-center justify-center mb-3">
            <StarIcon class="w-5 h-5" />
          </div>
          <p class="text-xs text-neutral-500">Rating Rata-rata</p>
          <div class="flex items-baseline gap-1.5 mt-1">
            <p class="font-display text-2xl font-bold text-neutral-900 dark:text-neutral-100">
              {{ psikologData?.rating_avg ? psikologData.rating_avg.toFixed(1) : '5.0' }}
            </p>
            <span class="text-xs text-neutral-400">({{ psikologData?.total_reviews || 0 }} ulasan)</span>
          </div>
        </div>
      </div>

      <!-- Sesi Hari Ini untuk Psikolog -->
      <div class="bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 p-6 shadow-xs">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="font-display font-semibold text-neutral-900 dark:text-neutral-100 text-base">
              Antrean Pasien Hari Ini
            </h3>
            <p class="text-xs text-neutral-500">Sesi konsultasi yang dijadwalkan hari ini</p>
          </div>
          <RouterLink to="/dashboard/konsultasi" class="text-xs text-rose-500 hover:text-rose-600 font-semibold">
            Ke Ruang Konsultasi & Catatan →
          </RouterLink>
        </div>

        <div v-if="!psikologData?.today_bookings || psikologData.today_bookings.length === 0" class="py-12 text-center text-neutral-400">
          <div class="text-3xl mb-2">☕</div>
          <p class="font-medium text-neutral-700 dark:text-neutral-300 text-sm">Tidak ada jadwal sesi hari ini</p>
          <p class="text-xs text-neutral-500 mt-1">Nikmati waktu luang Anda atau periksa jadwal praktek untuk pekan ini.</p>
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="booking in psikologData.today_bookings"
            :key="booking.id"
            class="p-4 rounded-2xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-200/70 dark:border-neutral-800 flex items-center justify-between gap-4"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 font-bold flex items-center justify-center text-sm shrink-0">
                {{ booking.pasien?.name?.charAt(0) || 'P' }}
              </div>
              <div>
                <h4 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                  {{ booking.pasien?.name }}
                </h4>
                <p class="text-xs text-neutral-500">
                  Jam: <strong class="text-rose-600 font-medium">{{ booking.start_time }} - {{ booking.end_time }}</strong>
                </p>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <a
                v-if="booking.room_id"
                :href="`https://meet.jit.si/${booking.room_id}`"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 transition-colors shadow-xs"
              >
                <VideoCameraIcon class="w-4 h-4" />
                Mulai / Masuk Sesi Video
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
