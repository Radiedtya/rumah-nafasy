<script setup lang="ts">
import { computed } from 'vue'
import { useBookingStore, formatRupiah } from '../../../stores/booking'
import BaseAvatar from '../../ui/BaseAvatar.vue'

const store = useBookingStore()

const psikolog = computed(() => store.psikolog)
const category = computed(() => store.selectedCategory)
const duration = computed(() => store.selectedDuration)
const order = computed(() => store.order)

const rateLabel = computed(() => {
  if (!category.value) return '—'
  const rate = psikolog.value?.custom_rate || category.value.base_price
  return `${formatRupiah(rate)} / 60m`
})
</script>

<template>
  <aside class="space-y-4 lg:sticky lg:top-4">
    <!-- Psikolog -->
    <div class="rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4">
      <p class="text-[11px] font-semibold uppercase tracking-wide text-[var(--muted)]">Psikolog Pilihan</p>
      <div v-if="psikolog" class="mt-3 flex items-center gap-3">
        <BaseAvatar :name="psikolog.name" :src="psikolog.avatar" size="md" />
        <div class="min-w-0">
          <p class="truncate text-sm font-semibold text-[var(--text)]">{{ psikolog.name }}</p>
          <p class="truncate text-xs text-[var(--accent)]">{{ psikolog.specialization || 'Psikolog Klinis' }}</p>
        </div>
      </div>
      <div v-else class="mt-3 space-y-2">
        <div class="h-9 w-9 animate-pulse rounded-full bg-[var(--muted)]/15" />
        <div class="h-3 w-2/3 animate-pulse rounded bg-[var(--muted)]/15" />
      </div>
      <p v-if="psikolog?.workplace" class="mt-2.5 truncate text-[11px] text-[var(--muted)]">
        {{ psikolog.workplace }}
      </p>
    </div>

    <!-- Rincian pesanan -->
    <div class="rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4">
      <p class="text-[11px] font-semibold uppercase tracking-wide text-[var(--muted)]">Rincian Pesanan</p>
      <dl class="mt-3 space-y-2.5 text-xs">
        <div class="flex items-center justify-between gap-3">
          <dt class="text-[var(--muted)]">Kategori</dt>
          <dd class="font-medium text-[var(--text)]">{{ category?.name ?? '—' }}</dd>
        </div>
        <div class="flex items-center justify-between gap-3">
          <dt class="text-[var(--muted)]">Tarif</dt>
          <dd class="tabular-nums text-[var(--text)]">{{ rateLabel }}</dd>
        </div>
        <div class="flex items-center justify-between gap-3">
          <dt class="text-[var(--muted)]">Durasi</dt>
          <dd class="font-medium text-[var(--text)]">{{ duration?.name ?? '—' }}</dd>
        </div>
        <div class="flex items-center justify-between gap-3">
          <dt class="text-[var(--muted)]">Media</dt>
          <dd class="font-medium text-[var(--text)]">{{ store.consultationType === 'video' ? 'Video Call' : 'Chat Teks' }}</dd>
        </div>
        <div v-if="order" class="flex items-center justify-between gap-3">
          <dt class="text-[var(--muted)]">No. Order</dt>
          <dd class="font-mono text-[11px] text-[var(--text)]">{{ order.order_number }}</dd>
        </div>
        <div class="flex items-center justify-between gap-3 border-t border-[var(--line)] pt-2.5">
          <dt class="font-semibold text-[var(--text)]">Total</dt>
          <dd class="text-base font-semibold tabular-nums text-[var(--accent)]">
            {{ formatRupiah(order?.calculated_price ?? store.calculatedPrice) }}
          </dd>
        </div>
      </dl>
    </div>
  </aside>
</template>
