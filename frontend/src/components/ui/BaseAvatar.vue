<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  name: string
  src?: string | null
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
}

const props = withDefaults(defineProps<Props>(), {
  src: null,
  size: 'md',
})

const sizes: Record<string, string> = {
  xs: 'w-6 h-6 text-[10px]',
  sm: 'w-8 h-8 text-xs',
  md: 'w-10 h-10 text-sm',
  lg: 'w-12 h-12 text-base',
  xl: 'w-16 h-16 text-2xl',
}

const initials = computed(() => {
  const n = props.name?.trim()
  if (!n) return '·'
  return n
    .split(/\s+/)
    .slice(0, 2)
    .map((w) => w.charAt(0).toUpperCase())
    .join('')
})
</script>

<template>
  <span
    class="relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-[var(--accent)]/12 font-semibold text-[var(--accent)] ring-1 ring-[var(--accent)]/15"
    :class="sizes[size]"
  >
    <img v-if="src" :src="src" :alt="name" class="absolute inset-0 h-full w-full object-cover" />
    <span v-else>{{ initials }}</span>
  </span>
</template>
