<script setup lang="ts">
import {
  DialogRoot,
  DialogTrigger,
  DialogPortal,
  DialogOverlay,
  DialogContent,
  DialogTitle,
  DialogClose,
} from 'reka-ui'
import { XMarkIcon } from '@heroicons/vue/24/outline'

interface Props {
  open?: boolean
  title?: string
  description?: string
  maxWidth?: string
}

const props = withDefaults(defineProps<Props>(), {
  open: undefined,
  title: '',
  description: '',
  maxWidth: 'max-w-md',
})

const emit = defineEmits<{ (e: 'update:open', v: boolean): void }>()
</script>

<template>
  <DialogRoot
    :open="open"
    @update:open="(v: boolean) => emit('update:open', v)"
  >
    <DialogTrigger v-if="$slots.trigger" as-child>
      <slot name="trigger" />
    </DialogTrigger>

    <DialogPortal>
      <DialogOverlay class="dialog-overlay fixed inset-0 z-50 bg-black/45 backdrop-blur-[2px]" />
      <DialogContent
        aria-describedby="undefined"
        class="dialog-content fixed left-1/2 top-1/2 z-50 w-[calc(100vw-2rem)] max-h-[90vh] overflow-y-auto rounded-2xl border border-[var(--line)] bg-[var(--surface)] p-6 shadow-2xl focus:outline-none"
        :class="maxWidth"
      >
        <div class="mb-4 flex items-start justify-between gap-4">
          <div>
            <DialogTitle class="text-sm font-semibold text-[var(--text)]">
              <slot name="title">{{ title }}</slot>
            </DialogTitle>
            <p v-if="description || $slots.description" class="mt-1 text-xs text-[var(--muted)]">
              <slot name="description">{{ description }}</slot>
            </p>
          </div>
          <DialogClose
            class="rounded-lg p-1 text-[var(--muted)] transition-colors hover:bg-[var(--muted)]/10 hover:text-[var(--text)]"
            aria-label="Tutup"
          >
            <XMarkIcon class="h-4 w-4" />
          </DialogClose>
        </div>

        <slot />
      </DialogContent>
    </DialogPortal>
  </DialogRoot>
</template>
