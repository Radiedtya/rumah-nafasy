<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router'
import { CheckIcon } from '@heroicons/vue/24/outline'

interface Props {
  current: 1 | 2 | 3 | 4
  clickable?: boolean
}

const props = withDefaults(defineProps<Props>(), { clickable: false })

const route = useRoute()
const slug = route.params.slug as string

const steps = [
  { step: 1, label: 'Paket' },
  { step: 2, label: 'Keluhan' },
  { step: 3, label: 'Jadwal' },
  { step: 4, label: 'Selesai' },
] as const

const SUFFIXES = ['', '/keluhan', '/jadwal', '/selesai'] as const

function stepTo(step: number) {
  return `/dashboard/booking/${slug}${SUFFIXES[step - 1]}`
}

function isClickable(step: number) {
  return props.clickable && step < props.current
}
</script>

<template>
  <ol class="flex items-center gap-1.5 sm:gap-2">
    <li v-for="(s, i) in steps" :key="s.step" class="flex min-w-0 items-center gap-1.5 sm:gap-2">
      <!-- Bubble -->
      <component
        :is="isClickable(s.step) ? RouterLink : 'div'"
        :to="isClickable(s.step) ? stepTo(s.step) : undefined"
        class="flex shrink-0 items-center gap-1.5"
        :class="isClickable(s.step) ? 'cursor-pointer' : 'cursor-default'"
        :aria-current="s.step === current ? 'step' : undefined"
      >
        <span
          class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border text-[11px] font-semibold transition-colors"
          :class="
            s.step < current
              ? 'border-[var(--accent)] bg-[var(--accent)] text-white'
              : s.step === current
                ? 'border-[var(--accent)] bg-[var(--accent)]/10 text-[var(--accent)]'
                : 'border-[var(--line)] bg-[var(--surface)] text-[var(--muted)]'
          "
        >
          <CheckIcon v-if="s.step < current" class="h-3.5 w-3.5" />
          <template v-else>{{ s.step }}</template>
        </span>
        <span
          class="hidden text-xs font-medium sm:block"
          :class="s.step === current ? 'text-[var(--text)]' : 'text-[var(--muted)]'"
        >
          {{ s.label }}
        </span>
      </component>

      <!-- Connector -->
      <span
        v-if="i < steps.length - 1"
        class="h-px w-4 shrink-0 sm:w-7"
        :class="s.step < current ? 'bg-[var(--accent)]' : 'bg-[var(--line)]'"
      />
    </li>
  </ol>
</template>
