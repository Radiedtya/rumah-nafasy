<script setup lang="ts">
interface Props {
  class?: string
}

defineProps<Props>()
</script>

<template>
  <div class="animate-pulse rounded-xl bg-[var(--muted)]/12" :class="$props.class" />
</template>
