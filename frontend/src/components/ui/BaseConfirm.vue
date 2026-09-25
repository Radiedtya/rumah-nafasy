<script setup lang="ts">
/**
 * BaseConfirm — Dialog konfirmasi custom untuk menggantikan confirm() bawaan browser.
 * Dibangun di atas reka-ui Dialog agar konsisten dengan BaseModal (fokus trap, ESC, overlay).
 */
import {
  DialogRoot,
  DialogPortal,
  DialogOverlay,
  DialogContent,
  DialogTitle,
} from 'reka-ui'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import BaseButton from './BaseButton.vue'

interface Props {
  open: boolean
  title?: string
  message?: string
  confirmText?: string
  cancelText?: string
  tone?: 'danger' | 'primary'
  loading?: boolean
}

withDefaults(defineProps<Props>(), {
  title: 'Konfirmasi',
  message: '',
  confirmText: 'Ya, Lanjutkan',
  cancelText: 'Batal',
  tone: 'primary',
  loading: false,
})

const emit = defineEmits<{
  (e: 'update:open', v: boolean): void
  (e: 'confirm'): void
}>()

function close() {
  emit('update:open', false)
}
</script>

<template>
  <DialogRoot :open="open" @update:open="(v: boolean) => emit('update:open', v)">
    <DialogPortal>
      <DialogOverlay class="fixed inset-0 z-50 bg-black/45 backdrop-blur-[2px]" />
      <DialogContent
        class="fixed left-1/2 top-1/2 z-50 w-[calc(100vw-2rem)] max-w-sm -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-[var(--line)] bg-[var(--surface)] p-6 shadow-2xl focus:outline-none"
        aria-describedby="undefined"
      >
        <div class="flex items-start gap-3">
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
            :class="tone === 'danger' ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400' : 'bg-[var(--accent)]/10 text-[var(--accent)]'"
          >
            <ExclamationTriangleIcon class="h-5 w-5" />
          </div>
          <div class="min-w-0 flex-1">
            <DialogTitle class="text-sm font-semibold text-[var(--text)]">
              {{ title }}
            </DialogTitle>
            <p v-if="message" class="mt-1 text-xs leading-relaxed text-[var(--muted)]">
              {{ message }}
            </p>
          </div>
        </div>

        <div class="mt-5 flex items-center gap-2">
          <BaseButton variant="secondary" size="md" class="flex-1" :disabled="loading" @click="close">
            {{ cancelText }}
          </BaseButton>
          <BaseButton
            :variant="tone === 'danger' ? 'danger' : 'primary'"
            size="md"
            class="flex-1"
            :disabled="loading"
            @click="emit('confirm')"
          >
            {{ loading ? 'Memproses…' : confirmText }}
          </BaseButton>
        </div>
      </DialogContent>
    </DialogPortal>
  </DialogRoot>
</template>
