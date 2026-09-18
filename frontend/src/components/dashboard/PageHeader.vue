<script setup lang="ts">
interface Props {
  title: string
  description?: string
}

defineProps<Props>()
</script>

<template>
  <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
    <div>
      <h1 class="text-lg font-semibold tracking-tight text-[var(--text)]">{{ title }}</h1>
      <p v-if="description" class="mt-1 max-w-2xl text-xs leading-relaxed text-[var(--muted)]">
        {{ description }}
      </p>
    </div>
    <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>
