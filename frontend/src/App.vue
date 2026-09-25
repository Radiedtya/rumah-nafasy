<script setup lang="ts">
import { ref, onMounted } from 'vue'
import BaseAlert from './components/ui/BaseAlert.vue'
import { registerAlertFn } from './composables/useAlert'
import type { AlertType } from './components/ui/BaseAlert.vue'

const alertRef = ref<InstanceType<typeof BaseAlert> | null>(null)

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