<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowRightIcon } from '@heroicons/vue/24/outline'
import { apiFetch } from '../../../lib/api'
import { useBookingStore, formatRupiah } from '../../../stores/booking'
import BaseButton from '../../../components/ui/BaseButton.vue'
import BaseBadge from '../../../components/ui/BaseBadge.vue'

const router = useRouter()
const store = useBookingStore()

const isSubmitting = ref(false)
const error = ref('')

const order = computed(() => store.order)
const canPay = computed(() => !!order.value && !store.paymentConfirmed)

// Kalau order belum ada (mis. refresh halaman langsung ke sini), kembali ke langkah 1
if (!order.value) {
  router.replace(
    store.psikolog?.slug
      ? `/dashboard/booking/${store.psikolog.slug}`
      : '/dashboard/psikolog',
  )
}

async function confirmPaymentMock() {
  if (!order.value) return
  isSubmitting.value = true
  error.value = ''
  try {
    await apiFetch(`webhooks/midtrans?mock=1&order_id=${order.value.order_number}`)
    store.paymentConfirmed = true
    router.push(`/dashboard/booking/${store.psikolog.slug}/jadwal`)
  } catch (err: any) {
    error.value = err.message || 'Gagal konfirmasi pembayaran'
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

    <section>
      <h2 class="text-sm font-semibold text-[var(--text)]">Ringkasan Tagihan</h2>
      <div class="mt-3 space-y-3 rounded-xl border border-[var(--line)] bg-[var(--surface)] p-5 text-xs">
        <div class="flex items-center justify-between gap-3">
          <span class="text-[var(--muted)]">Nomor Order</span>
          <strong class="font-mono text-[var(--text)]">{{ order?.order_number }}</strong>
        </div>
        <div class="flex items-center justify-between gap-3">
          <span class="text-[var(--muted)]">Layanan</span>
          <span class="font-medium text-[var(--text)]">
            {{ store.selectedCategory?.name ?? '—' }} · {{ store.selectedDuration?.name ?? '—' }}
          </span>
        </div>
        <div class="flex items-center justify-between gap-3">
          <span class="text-[var(--muted)]">Total Tagihan</span>
          <strong class="text-base tabular-nums text-[var(--accent)]">
            {{ formatRupiah(order?.calculated_price) }}
          </strong>
        </div>
        <div class="flex items-center justify-between gap-3 border-t border-[var(--line)] pt-3">
          <span class="text-[var(--muted)]">Status Pembayaran</span>
          <BaseBadge :tone="store.paymentConfirmed ? 'success' : 'warning'">
            {{ store.paymentConfirmed ? 'Lunas' : 'Pending' }}
          </BaseBadge>
        </div>
      </div>
    </section>

    <template v-if="!store.paymentConfirmed">
      <section class="rounded-xl border border-dashed border-[var(--line)] bg-[var(--muted)]/5 p-5">
        <h3 class="text-sm font-semibold text-[var(--text)]">Metode Pembayaran</h3>
        <p class="mt-1.5 text-xs leading-relaxed text-[var(--muted)]">
          Di lingkungan development ini, gateway Midtrans berjalan dalam mode sandbox. Anda dapat langsung
          melakukan <strong class="text-[var(--text)]">simulasi pembayaran berhasil</strong> yang terhubung ke
          webhook backend Laravel.
        </p>
        <BaseButton
          variant="success"
          size="md"
          class="mt-4 w-full"
          :disabled="isSubmitting || !canPay"
          @click="confirmPaymentMock"
        >
          {{ isSubmitting ? 'Menyinkronkan…' : 'Konfirmasi & Selesaikan Pembayaran (Simulasi)' }}
        </BaseButton>
      </section>
    </template>

    <div v-else class="flex items-center justify-between gap-4 rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4">
      <p class="text-xs text-[var(--muted)]">Pembayaran sudah dikonfirmasi. Lanjutkan ke pemilihan jadwal.</p>
      <BaseButton size="md" @click="router.push(`/dashboard/booking/${store.psikolog.slug}/jadwal`)">
        Pilih Jadwal
        <ArrowRightIcon class="h-3.5 w-3.5" />
      </BaseButton>
    </div>
  </div>
</template>
