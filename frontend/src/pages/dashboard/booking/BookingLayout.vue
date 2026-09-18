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

watch(slug, () => {
  if (slug.value) loadDetail()
})

onMounted(async () => {
  // Wizard butuh sesi login. Beri jeda untuk auto-login dev di DashboardLayout
  // (child onMounted berjalan lebih dulu daripada parent).
  if (!auth.isAuthenticated) {
    const started = Date.now()
    while (!auth.isAuthenticated && Date.now() - started < 2000) {
      await new Promise((r) => setTimeout(r, 100))
    }
    if (!auth.isAuthenticated) {
      router.replace({ path: '/login', query: { redirect: route.fullPath } })
      return
    }
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
