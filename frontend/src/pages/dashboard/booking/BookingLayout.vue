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
  if (route.path.endsWith('/keluhan')) return 2
  if (route.path.endsWith('/jadwal')) return 3
  if (route.path.endsWith('/selesai')) return 4
  return 1
}
const step = computed(() => currentStep())

async function loadCatalog() {
  if (store.categories.length > 0) return
  try {
    const res = await apiFetch('public/categories')
    store.categories = res.data || []
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

watch(slug, () => {
  if (slug.value) loadDetail()
})

// ── Gate urutan langkah wizard ──────────────────────────────────────────────
// Selesai hanya boleh diakses setelah pengajuan terkirim (ada hasil booking).
watch(
  [step, booted],
  ([s, ready]) => {
    if (!ready || notFound.value) return
    if (s === 4 && !store.confirmedBooking) {
      router.replace(`/dashboard/booking/${slug.value}/jadwal`)
    }
  },
  { immediate: false },
)

onMounted(async () => {
  // Gate sesi: tanpa token → login (lapisan kedua di atas guard router).
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

  await Promise.all([loadCatalog(), loadDetail()])
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
          {{ store.psikolog?.specialization || 'Psikolog Klinis' }} · Pilih paket, tentukan jadwal, tanpa pembayaran di aplikasi.
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
