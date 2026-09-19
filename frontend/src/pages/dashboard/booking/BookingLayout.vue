<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'
import { apiFetch } from '../../../lib/api'
import { useAuthStore } from '../../../stores/auth'
import { useBookingStore } from '../../../stores/booking'
import BookingSteps from '../../../components/dashboard/booking/BookingSteps.vue'
import BookingSummary from '../../../components/dashboard/booking/BookingSummary.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const store = useBookingStore()

const slug = computed(() => route.params.slug as string)
const notFound = ref(false)
const booted = ref(false)

function currentStep(): 1 | 2 | 3 | 4 {
  if (route.path.endsWith('/pembayaran')) return 2
  if (route.path.endsWith('/jadwal')) return 3
  if (route.path.endsWith('/selesai')) return 4
  return 1
}
const step = computed(() => currentStep())

async function loadCatalog() {
  if (store.categories.length > 0 && store.durations.length > 0) {
    store.applyCatalogDefaults()
    return
  }
  try {
    const [catRes, durRes] = await Promise.all([
      apiFetch('public/categories'),
      apiFetch('public/durations'),
    ])
    store.categories = catRes.data || []
    store.durations = durRes.data || []
    store.applyCatalogDefaults()
  } catch (e) {
    console.error('Failed loading booking catalog', e)
  }
}

async function loadDetail() {
  store.startFor(slug.value)
  notFound.value = false
  if (store.psikolog) return
  try {
    const res = await apiFetch(`public/psikolog/${slug.value}`)
    if (res.data) {
      store.psikolog = res.data
    } else {
      notFound.value = true
    }
  } catch (e) {
    console.error('Failed loading psikolog detail', e)
    notFound.value = true
  } finally {
    store.loadingDetail = false
  }
}

/**
 * Resume sesi pembayaran (mis. kembali dari halaman Midtrans via
 * /dashboard/booking/payment/{order_number} yang diarahkan ke sini).
 * Endpoint pembayaran bersifat idempotent untuk order pending.
 */
async function resumePayment(orderId: string) {
  try {
    const payRes = await apiFetch<{
      payment: { status: string }
      snap_url: string
      is_mock?: boolean
    }>(`pasien/orders/${orderId}/payment`, { method: 'POST' })
    store.payment = payRes.data.payment
    store.snapUrl = payRes.data.snap_url
    store.isMockPayment = payRes.data.is_mock === true

    const orderRes = await apiFetch(`pasien/orders/${orderId}`)
    store.order = orderRes.data
    store.paymentConfirmed = orderRes.data?.status === 'paid'
  } catch {
    // Order tidak ditemukan / bukan milik user — biarkan wizard mulai dari awal
  }
}

watch(slug, () => {
  if (slug.value) loadDetail()
})

// ── Gate urutan langkah wizard ──────────────────────────────────────────────
// Tidak boleh loncat ke Jadwal/Selesai sebelum order terbayar.
watch(
  [step, booted],
  ([s, ready]) => {
    if (!ready || notFound.value) return
    const paid = store.paymentConfirmed || store.order?.status === 'paid'
    if (s >= 3 && !paid) {
      router.replace(
        store.psikolog?.slug
          ? `/dashboard/booking/${store.psikolog.slug}/pembayaran`
          : `/dashboard/booking/${slug.value}/pembayaran`,
      )
    } else if (s === 2 && !store.order && !route.query.order) {
      router.replace(`/dashboard/booking/${slug.value}`)
    }
  },
  { immediate: false },
)

onMounted(async () => {
  // Gate sesi: tanpa token → login. (Guard router sudah menjalankan ini,
  // ini lapisan kedua untuk akses langsung via URL.)
  if (!auth.token) {
    router.replace({ path: '/login', query: { redirect: route.fullPath } })
    return
  }
  if (!auth.user) {
    await auth.fetchMe()
    if (!auth.user) {
      router.replace({ path: '/login', query: { redirect: route.fullPath } })
      return
    }
  }

  // Resume pembayaran jika kembali dari payment gateway (?order=<id>)
  const orderId = route.query.order
  if (orderId && !store.order) {
    await resumePayment(String(orderId))
  }

  await loadCatalog()
  await loadDetail()
  booted.value = true
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-6">
      <RouterLink
        to="/dashboard/psikolog"
        class="inline-flex items-center gap-1.5 text-xs font-medium text-[var(--muted)] transition-colors hover:text-[var(--text)]"
      >
        <ArrowLeftIcon class="h-3.5 w-3.5" />
        Kembali ke daftar psikolog
      </RouterLink>

      <div v-if="store.loadingDetail" class="mt-3 space-y-2">
        <div class="h-6 w-72 animate-pulse rounded bg-[var(--muted)]/15" />
        <div class="h-4 w-48 animate-pulse rounded bg-[var(--muted)]/10" />
      </div>
      <div v-else-if="notFound" class="mt-3">
        <h1 class="text-xl font-semibold tracking-tight text-[var(--text)]">Psikolog tidak ditemukan</h1>
        <p class="mt-1 text-sm text-[var(--muted)]">
          Data psikolog tidak tersedia atau belum terverifikasi. Silakan pilih dari daftar.
        </p>
      </div>
      <template v-else>
        <h1 class="mt-3 text-xl font-semibold tracking-tight text-[var(--text)]">
          Konsultasi dengan {{ store.psikolog?.name }}
        </h1>
        <p class="mt-1 text-sm text-[var(--muted)]">
          {{ store.psikolog?.specialization || 'Psikolog Klinis' }} · Pilih paket, bayar, lalu tentukan jadwal.
        </p>
      </template>

      <div class="mt-5 border-b border-[var(--line)] pb-5">
        <BookingSteps :current="step" clickable />
      </div>
    </div>

    <!-- Body: form + ringkasan -->
    <div v-if="booted && !notFound" class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
      <div class="min-w-0">
        <RouterView />
      </div>
      <BookingSummary />
    </div>

    <!-- Belum selesai dimuat -->
    <div v-else-if="!notFound" class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
      <div class="space-y-3">
        <div v-for="i in 3" :key="i" class="h-20 animate-pulse rounded-xl bg-[var(--muted)]/10" />
      </div>
      <div class="h-56 animate-pulse rounded-xl bg-[var(--muted)]/10" />
    </div>
  </div>
</template>
