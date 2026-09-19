<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowRightIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline'
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

/**
 * Lanjut ke pembayaran.
 * Backend mengembalikan `snap_url` (hosted payment Midtrans).
 * Jika gateway belum dikonfigurasi, respons berisi `is_mock: true` —
 * UI simulasi hanya muncul kalau backend yang menyatakan mock.
 */
onMounted(async () => {
  if (!canPay.value) return
  try {
    const res = await apiFetch<{
      payment: { status: string; payment_url: string | null }
      snap_url: string
      is_mock?: boolean
    }>(`pasien/orders/${order.value.id}/payment`, { method: 'POST' })
    store.payment = res.data.payment
    store.snapUrl = res.data.snap_url
    store.isMockPayment = res.data.is_mock === true
  } catch (err: any) {
    error.value = err.message || 'Gagal menyiapkan pembayaran'
  }
})

async function confirmMockPayment() {
  if (!order.value) return
  isSubmitting.value = true
  error.value = ''
  try {
    // Endpoint simulasi resmi: auth + hanya pemilik order + hanya saat gateway nonaktif
    await apiFetch(`pasien/orders/${order.value.id}/mock-success`, { method: 'POST' })
    // Sinkronkan status terbaru dari server (sumber kebenaran), bukan asumsi lokal
    const res = await apiFetch(`pasien/orders/${order.value.id}`)
    store.order = res.data
    store.paymentConfirmed = res.data?.status === 'paid'
    if (store.paymentConfirmed) {
      router.push(`/dashboard/booking/${store.psikolog.slug}/jadwal`)
    }
  } catch (err: any) {
    error.value = err.message || 'Gagal mengonfirmasi pembayaran'
  } finally {
    isSubmitting.value = false
  }
}

async function goToSnapPayment() {
  if (!store.snapUrl) return
  // Hosted payment page — jangan dibuka di tab yang sama agar wizard tidak mati
  window.open(store.snapUrl, '_blank', 'noopener')
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
      <!-- Pembayaran nyata via Midtrans Snap -->
      <section v-if="!store.isMockPayment" class="rounded-xl border border-[var(--line)] bg-[var(--surface)] p-5">
        <h3 class="text-sm font-semibold text-[var(--text)]">Metode Pembayaran</h3>
        <div class="mt-2 flex items-start gap-2.5 rounded-lg bg-[var(--muted)]/6 p-3">
          <ShieldCheckIcon class="mt-0.5 h-4.5 w-4.5 shrink-0 text-[var(--accent)]" />
          <p class="text-xs leading-relaxed text-[var(--muted)]">
            Anda akan diarahkan ke halaman pembayaran aman <strong class="text-[var(--text)]">Midtrans</strong>
            (virtual account, e-wallet, kartu, dll). Halaman wizard ini jangan ditutup —
            kembali ke sini setelah pembayaran selesai.
          </p>
        </div>
        <BaseButton
          variant="success"
          size="md"
          class="mt-4 w-full"
          :disabled="isSubmitting || !store.snapUrl"
          @click="goToSnapPayment"
        >
          {{ isSubmitting ? 'Menyiapkan…' : 'Bayar via Midtrans' }}
        </BaseButton>
      </section>

      <!-- Simulasi — hanya jika backend menyatakan mode mock (gateway belum dikonfigurasi) -->
      <section v-else class="rounded-xl border border-dashed border-[var(--line)] bg-[var(--muted)]/5 p-5">
        <h3 class="text-sm font-semibold text-[var(--text)]">Metode Pembayaran</h3>
        <p class="mt-1.5 text-xs leading-relaxed text-[var(--muted)]">
          Gateway pembayaran belum aktif di server ini. Tombol di bawah menjalankan
          <strong class="text-[var(--text)]">simulasi pembayaran</strong> melalui endpoint khusus
          yang hanya tersedia saat Midtrans tidak dikonfigurasi.
        </p>
        <BaseButton
          variant="success"
          size="md"
          class="mt-4 w-full"
          :disabled="isSubmitting || !canPay"
          @click="confirmMockPayment"
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
