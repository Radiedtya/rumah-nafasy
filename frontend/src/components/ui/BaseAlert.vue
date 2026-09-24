<script setup lang="ts">
/**
 * BaseAlert — Custom alert/toast system untuk menggantikan alert() bawaan browser.
 */
import { ref, onBeforeUnmount } from 'vue'
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  InformationCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

export type AlertType = 'success' | 'error' | 'warning' | 'info'

export interface AlertItem {
  id: number
  type: AlertType
  title?: string
  message: string
  duration?: number
}

const alerts = ref<AlertItem[]>([])
let nextId = 1
const timers = new Map<number, ReturnType<typeof setTimeout>>()

function show(item: Omit<AlertItem, 'id'>): number {
  const id = nextId++
  alerts.value.push({ ...item, id })
  const duration = item.duration ?? 4500
  if (duration > 0) {
    const t = setTimeout(() => remove(id), duration)
    timers.set(id, t)
  }
  return id
}

function remove(id: number) {
  const timer = timers.get(id)
  if (timer) { clearTimeout(timer); timers.delete(id) }
  alerts.value = alerts.value.filter((a) => a.id !== id)
}

defineExpose({ show, remove })

onBeforeUnmount(() => {
  timers.forEach((t) => clearTimeout(t))
  timers.clear()
})

const iconMap = {
  success: CheckCircleIcon,
  error: XCircleIcon,
  warning: ExclamationTriangleIcon,
  info: InformationCircleIcon,
}
</script>

<template>
  <Teleport to="body">
    <div class="alert-container" aria-live="polite">
      <TransitionGroup name="alert-slide" tag="div" class="alert-list">
        <div
          v-for="alert in alerts"
          :key="alert.id"
          class="alert-item"
          :class="`alert-${alert.type}`"
          role="alert"
        >
          <component :is="iconMap[alert.type]" class="alert-icon" aria-hidden="true" />
          <div class="alert-body">
            <p v-if="alert.title" class="alert-title">{{ alert.title }}</p>
            <p class="alert-message">{{ alert.message }}</p>
          </div>
          <button type="button" class="alert-close" aria-label="Tutup" @click="remove(alert.id)">
            <XMarkIcon class="h-4 w-4" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.alert-container {
  position: fixed;
  top: 1.25rem;
  right: 1.25rem;
  z-index: 9999;
  width: min(calc(100vw - 2.5rem), 400px);
  pointer-events: none;
}
.alert-list { display: flex; flex-direction: column; gap: 0.5rem; }

.alert-item {
  pointer-events: all;
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  border: 1px solid;
  border-radius: 0.875rem;
  box-shadow: 0 8px 24px rgba(0,0,0,.12), 0 2px 8px rgba(0,0,0,.08);
}

.alert-success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
.alert-error   { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
.alert-warning { background: #fffbeb; border-color: #fde68a; color: #92400e; }
.alert-info    { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }

:global([data-theme='dark']) .alert-success { background: #052e16; border-color: #166534; color: #86efac; }
:global([data-theme='dark']) .alert-error   { background: #450a0a; border-color: #991b1b; color: #fca5a5; }
:global([data-theme='dark']) .alert-warning { background: #451a03; border-color: #92400e; color: #fcd34d; }
:global([data-theme='dark']) .alert-info    { background: #0c1a3a; border-color: #1e40af; color: #93c5fd; }

.alert-icon { flex-shrink: 0; width: 1.25rem; height: 1.25rem; margin-top: 0.05rem; }
.alert-body { flex: 1; min-width: 0; }
.alert-title { margin: 0 0 0.2rem; font-size: 0.8125rem; font-weight: 600; line-height: 1.3; }
.alert-message { margin: 0; font-size: 0.75rem; line-height: 1.45; opacity: 0.85; }
.alert-title + .alert-message { opacity: 0.75; }

.alert-close {
  flex-shrink: 0; display: flex; align-items: center; justify-content: center;
  width: 1.5rem; height: 1.5rem;
  border: none; border-radius: 0.375rem;
  background: transparent; color: currentColor; cursor: pointer;
  opacity: 0.5; transition: opacity 150ms, background 150ms;
}
.alert-close:hover { opacity: 1; background: rgba(0,0,0,.1); }

.alert-slide-enter-active { transition: all 320ms cubic-bezier(0.16, 1, 0.3, 1); }
.alert-slide-leave-active { transition: all 240ms ease; }
.alert-slide-enter-from { opacity: 0; transform: translateX(100%) scale(0.95); }
.alert-slide-leave-to   { opacity: 0; transform: translateX(60%) scale(0.95); }
</style>
