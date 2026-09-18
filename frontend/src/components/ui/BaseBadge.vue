<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  tone?: 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'accent'
}

const props = withDefaults(defineProps<Props>(), {
  tone: 'neutral',
})

const toneClasses = computed(
  () =>
    ({
      neutral: 'bg-neutral-500/10 text-[var(--muted)] ring-[var(--line)]',
      success: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 ring-emerald-500/20',
      warning: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 ring-amber-500/20',
      danger: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 ring-rose-500/20',
      info: 'bg-sky-500/10 text-sky-600 dark:text-sky-400 ring-sky-500/20',
      accent: 'bg-[var(--accent)]/10 text-[var(--accent)] ring-[var(--accent)]/20',
    })[props.tone],
)
</script>

<template>
  <span
    class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide ring-1 ring-inset"
    :class="toneClasses"
  >
    <slot />
  </span>
</template>
