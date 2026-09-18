<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  StarIcon,
  CheckBadgeIcon,
  AcademicCapIcon,
  ArrowRightIcon,
  MapPinIcon,
} from '@heroicons/vue/24/outline'
import { apiFetch } from '../../lib/api'
import RekaAutocomplete from '../../components/ui/RekaAutocomplete.vue'
import PageHeader from '../../components/dashboard/PageHeader.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseBadge from '../../components/ui/BaseBadge.vue'
import BaseAvatar from '../../components/ui/BaseAvatar.vue'
import BaseSkeleton from '../../components/ui/BaseSkeleton.vue'
import BaseEmpty from '../../components/ui/BaseEmpty.vue'

const router = useRouter()

// State
const loading = ref(true)
const psikologList = ref<any[]>([])
const specializations = ref<any[]>([])

// Filter & Search
const selectedSpecSlug = ref<string>('')
const sortBy = ref('rating')

// Load initial data
async function loadData() {
  loading.value = true
  try {
    const specRes = await apiFetch('public/specializations')
    specializations.value = specRes.data || []

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

// Arahkan ke halaman wizard booking (bukan modal)
function openBooking(psikolog: any) {
  if (!psikolog?.slug) return
  router.push(`/dashboard/booking/${psikolog.slug}`)
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
  </div>
</template>
