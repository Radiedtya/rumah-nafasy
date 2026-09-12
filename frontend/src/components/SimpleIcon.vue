<script setup lang="ts">
import type { SimpleIcon } from 'simple-icons'

withDefaults(defineProps<{
  icon: SimpleIcon
  size?: number | string
  title?: string
}>(), {
  size: 20,
})
</script>

<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="currentColor"
    role="img"
    :aria-hidden="title ? undefined : true"
    :aria-label="title"
    focusable="false"
  >
    <title v-if="title">{{ title }}</title>
    <path :d="icon.path" />
  </svg>
</template>