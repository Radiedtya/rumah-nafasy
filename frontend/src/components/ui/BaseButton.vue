<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  variant?: 'primary' | 'secondary' | 'danger' | 'success' | 'ghost'
  size?: 'xs' | 'sm' | 'md'
  disabled?: boolean
  type?: 'button' | 'submit'
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'sm',
  disabled: false,
  type: 'button',
})

const classes = computed(() => {
  const base =
    'inline-flex items-center justify-center gap-1.5 rounded-lg font-medium whitespace-nowrap transition-all duration-150 outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent)]/30 disabled:opacity-50 disabled:cursor-not-allowed select-none'

  const sizes: Record<string, string> = {
    xs: 'h-7 px-2.5 text-[11px]',
    sm: 'h-8.5 px-3.5 text-xs',
    md: 'h-10 px-4 text-sm',
  }

  const variants: Record<string, string> = {
    primary: 'bg-[var(--accent)] text-white hover:bg-[var(--accent-hover)] active:scale-[0.98] shadow-xs',
    secondary:
      'border border-[var(--line)] bg-[var(--surface)] text-[var(--text)] hover:bg-[var(--muted)]/8 active:scale-[0.98]',
    ghost:
      'text-[var(--muted)] hover:bg-[var(--muted)]/8 hover:text-[var(--text)]',
    danger: 'bg-rose-600 text-white hover:bg-rose-700 active:scale-[0.98] shadow-xs',
    success: 'bg-emerald-600 text-white hover:bg-emerald-700 active:scale-[0.98] shadow-xs',
  }

  return [base, sizes[props.size], variants[props.variant]].join(' ')
})
</script>

<template>
  <button :type="type" :disabled="disabled" :class="classes">
    <slot />
  </button>
</template>
