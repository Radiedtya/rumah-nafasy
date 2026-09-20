<script setup lang="ts">
import { computed } from 'vue'
import BaseBadge from '../ui/BaseBadge.vue'

interface Props {
  status: string
}

const props = defineProps<Props>()

const tone = computed(() => {
  const s = props.status?.toLowerCase()
  if (['confirmed', 'paid', 'completed', 'scheduled', 'done'].includes(s)) return 'success'
  if (['in_progress', 'pending_payment', 'pending', 'pending_psikolog'].includes(s)) return 'warning'
  if (['cancelled', 'rejected', 'failed', 'expired'].includes(s)) return 'danger'
  return 'neutral'
})

const LABELS: Record<string, string> = {
  pending_psikolog: 'Menunggu Persetujuan',
  rejected: 'Ditolak',
  confirmed: 'Terjadwal',
  in_progress: 'Berlangsung',
  completed: 'Selesai',
  cancelled: 'Dibatalkan',
}

const label = computed(() => LABELS[props.status?.toLowerCase()] ?? (props.status || '').replace(/_/g, ' '))
</script>

<template>
  <BaseBadge :tone="tone">{{ label }}</BaseBadge>
</template>
