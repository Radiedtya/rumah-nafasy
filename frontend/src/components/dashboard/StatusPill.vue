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
  if (['in_progress', 'pending_payment', 'pending'].includes(s)) return 'warning'
  if (['cancelled', 'failed', 'expired'].includes(s)) return 'danger'
  return 'neutral'
})

const label = computed(() => (props.status || '').replace(/_/g, ' '))
</script>

<template>
  <BaseBadge :tone="tone">{{ label }}</BaseBadge>
</template>
