<script setup lang="ts">
import type { Component } from 'vue'

interface Props {
  label: string
  value: string | number
  sub?: string
  icon?: Component
  tone?: 'default' | 'accent' | 'success' | 'warning'
}

withDefaults(defineProps<Props>(), {
  tone: 'default',
  sub: '',
  icon: undefined,
})
</script>

<template>
  <div
    class="rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4 transition-shadow duration-150 hover:shadow-xs"
  >
    <div class="flex items-start justify-between gap-2">
      <p class="text-[11px] font-medium leading-snug text-[var(--muted)]">{{ label }}</p>
      <span
        v-if="icon"
        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg"
        :class="{
          'bg-[var(--accent)]/10 text-[var(--accent)]': tone === 'accent',
          'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400': tone === 'success',
          'bg-amber-500/10 text-amber-600 dark:text-amber-400': tone === 'warning',
          'bg-[var(--muted)]/10 text-[var(--muted)]': tone === 'default',
        }"
      >
        <component :is="icon" class="h-3.5 w-3.5" />
      </span>
    </div>
    <p class="mt-2 text-xl font-semibold tracking-tight text-[var(--text)] tabular-nums">
      {{ value }}
    </p>
    <p v-if="sub" class="mt-0.5 text-[11px] text-[var(--muted)]">{{ sub }}</p>
  </div>
</template>
