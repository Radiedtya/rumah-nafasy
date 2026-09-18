<script setup lang="ts">
interface Props {
  icon?: string
  title: string
  description?: string
}

defineProps<Props>()
</script>

<template>
  <div class="flex flex-col items-center justify-center px-6 py-14 text-center">
    <div
      class="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-[var(--muted)]/10 text-lg text-[var(--muted)]"
    >
      {{ icon || '◌' }}
    </div>
    <p class="text-sm font-medium text-[var(--text)]">{{ title }}</p>
    <p v-if="description" class="mt-1 max-w-xs text-xs leading-relaxed text-[var(--muted)]">
      {{ description }}
    </p>
    <div v-if="$slots.default" class="mt-4">
      <slot />
    </div>
  </div>
</template>
