<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import BaseAlert from './components/ui/BaseAlert.vue'
import { registerAlertFn } from './composables/useAlert'
import type { AlertType } from './components/ui/BaseAlert.vue'

const alertRef = ref<InstanceType<typeof BaseAlert> | null>(null)
const route = useRoute()

// Reset the document scroll position after each successful route navigation.
watch(
  () => route.fullPath,
  () => {
    if (typeof window !== 'undefined') window.scrollTo(0, 0)
  },
  { flush: 'post' },
)

onMounted(() => {
  if (alertRef.value) {
    registerAlertFn((opts: { type: AlertType; message: string; title?: string; duration?: number }) =>
      alertRef.value!.show(opts)
    )
  }
})
</script>

<template>
  <RouterView />
  <BaseAlert ref="alertRef" />
</template>
