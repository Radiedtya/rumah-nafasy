import { ref } from 'vue'
import type { AlertType } from '../components/ui/BaseAlert.vue'

interface ShowOptions {
  type?: AlertType
  title?: string
  message: string
  duration?: number
}

// Global alert queue — populated by BaseAlert component via plugin
type AlertFn = (opts: Omit<ShowOptions, 'type'> & { type: AlertType }) => number

const _alertFn = ref<AlertFn | null>(null)

export function registerAlertFn(fn: AlertFn) {
  _alertFn.value = fn
}

export function useAlert() {
  function show(opts: ShowOptions) {
    if (_alertFn.value) return _alertFn.value({ type: 'info', ...opts })
    // Fallback saat belum mount
    console.warn('[useAlert]', opts.message)
    return -1
  }

  return {
    show,
    success: (message: string, title?: string, duration?: number) =>
      show({ type: 'success', message, title, duration }),
    error: (message: string, title?: string, duration?: number) =>
      show({ type: 'error', message, title, duration }),
    warning: (message: string, title?: string, duration?: number) =>
      show({ type: 'warning', message, title, duration }),
    info: (message: string, title?: string, duration?: number) =>
      show({ type: 'info', message, title, duration }),
  }
}
