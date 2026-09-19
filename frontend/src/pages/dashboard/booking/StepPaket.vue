<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  VideoCameraIcon,
  ChatBubbleLeftRightIcon,
  ArrowRightIcon,
} from '@heroicons/vue/24/outline'
import { apiFetch } from '../../../lib/api'
import { useBookingStore, formatRupiah } from '../../../stores/booking'
import BaseButton from '../../../components/ui/BaseButton.vue'

const router = useRouter()
const store = useBookingStore()

const isSubmitting = ref(false)
const error = ref('')

const canProceed = computed(() => !!store.selectedCategory && !!store.selectedDuration)

async function proceedToPayment() {
  if (!canProceed.value || !store.psikolog) return

  isSubmitting.value = true
  error.value = ''

  try {
    // 1. Buat order
    const orderRes = await apiFetch('pasien/orders', {
      method: 'POST',
      body: JSON.stringify({
        psikolog_id: store.psikolog.id,
        category_id: store.selectedCategory.id,
        duration_id: store.selectedDuration.id,
        consultation_type: store.consultationType,
      }),
    })
    store.order = orderRes.data

    // 2. Buat pembayaran
    const paymentRes = await apiFetch(`pasien/orders/${store.order.id}/payment`, {
      method: 'POST',
    })
    store.payment = paymentRes.data

    router.push(`/dashboard/booking/${store.psikolog.slug}/pembayaran`)
  } catch (err: any) {
    error.value = err.message || 'Gagal memproses order'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <div v-if="error" class="rounded-lg bg-rose-500/10 px-3.5 py-2.5 text-xs text-rose-600 dark:text-rose-400">
      {{ error }}
    </div>

    <!-- 1. Kategori klien -->
    <section>
      <h2 class="text-sm font-semibold text-[var(--text)]">1. Pilih Kategori Klien</h2>
      <p class="mt-0.5 text-xs text-[var(--muted)]">Tarif mengikuti kategori klien; psikolog dapat menetapkan tarif khusus.</p>
      <div class="mt-3 grid gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
        <button
          v-for="cat in store.categories"
          :key="cat.id"
          type="button"
          class="rounded-xl border p-4 text-left transition-colors"
          :class="
            store.selectedCategory?.id === cat.id
              ? 'border-[var(--accent)] bg-[var(--accent)]/8 ring-1 ring-[var(--accent)]/40'
              : 'border-[var(--line)] hover:bg-[var(--muted)]/6'
          "
          @click="store.selectedCategory = cat"
        >
          <p class="text-sm font-semibold text-[var(--text)]">{{ cat.name }}</p>
          <p class="mt-1 text-xs tabular-nums text-[var(--muted)]">
            {{ formatRupiah(cat.base_price) }} / 60 menit
          </p>
        </button>
      </div>
    </section>

    <!-- 2. Durasi -->
    <section>
      <h2 class="text-sm font-semibold text-[var(--text)]">2. Pilih Durasi Sesi</h2>
      <div class="mt-3 grid gap-2.5 sm:grid-cols-3">
        <button
          v-for="dur in store.durations"
          :key="dur.id"
          type="button"
          class="rounded-xl border p-4 text-left transition-colors"
          :class="
            store.selectedDuration?.id === dur.id
              ? 'border-[var(--accent)] bg-[var(--accent)]/8 ring-1 ring-[var(--accent)]/40'
              : 'border-[var(--line)] hover:bg-[var(--muted)]/6'
          "
          @click="store.selectedDuration = dur"
        >
          <p class="text-sm font-semibold text-[var(--text)]">{{ dur.name }}</p>
          <p class="mt-1 text-xs tabular-nums text-[var(--muted)]">{{ dur.multiplier }}x harga · {{ dur.minutes }} menit</p>
        </button>
      </div>
    </section>

    <!-- 3. Media -->
    <section>
      <h2 class="text-sm font-semibold text-[var(--text)]">3. Media Konsultasi</h2>
      <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
        <button
          type="button"
          class="flex items-center gap-3 rounded-xl border p-4 text-left transition-colors"
          :class="
            store.consultationType === 'video'
              ? 'border-[var(--accent)] bg-[var(--accent)]/8 ring-1 ring-[var(--accent)]/40'
              : 'border-[var(--line)] hover:bg-[var(--muted)]/6'
          "
          @click="store.consultationType = 'video'"
        >
          <VideoCameraIcon class="h-5 w-5 shrink-0 text-[var(--accent)]" />
          <div>
            <p class="text-sm font-semibold text-[var(--text)]">Video Call</p>
            <p class="text-xs text-[var(--muted)]">Sesi via ruang Jitsi Meet</p>
          </div>
        </button>
        <button
          type="button"
          class="flex items-center gap-3 rounded-xl border p-4 text-left transition-colors"
          :class="
            store.consultationType === 'chat'
              ? 'border-[var(--accent)] bg-[var(--accent)]/8 ring-1 ring-[var(--accent)]/40'
              : 'border-[var(--line)] hover:bg-[var(--muted)]/6'
          "
          @click="store.consultationType = 'chat'"
        >
          <ChatBubbleLeftRightIcon class="h-5 w-5 shrink-0 text-[var(--accent)]" />
          <div>
            <p class="text-sm font-semibold text-[var(--text)]">Chat Teks</p>
            <p class="text-xs text-[var(--muted)]">Asinkron via pesan</p>
          </div>
        </button>
      </div>
    </section>

    <!-- Footer aksi -->
    <div class="flex items-center justify-between gap-4 rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4">
      <div>
        <p class="text-[11px] text-[var(--muted)]">Estimasi Total Biaya</p>
        <p class="text-xl font-semibold tabular-nums text-[var(--accent)]">
          {{ formatRupiah(store.calculatedPrice) }}
        </p>
      </div>
      <BaseButton size="md" :disabled="!canProceed || isSubmitting" @click="proceedToPayment">
        {{ isSubmitting ? 'Membuat Order…' : 'Lanjut ke Pembayaran' }}
        <ArrowRightIcon class="h-3.5 w-3.5" />
      </BaseButton>
    </div>
  </div>
</template>
