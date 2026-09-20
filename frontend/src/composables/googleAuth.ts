import { onBeforeUnmount } from 'vue'
import { BACKEND_URL } from '../lib/config'

/**
 * Composable Login Google via POPUP (pola GitHub/Vercel).
 * Dipakai bersama oleh halaman Login & Register.
 *
 * Keamanan postMessage (berlapis):
 * 1. event.origin === origin kita sendiri
 * 2. event.source === popup yang KITA buka (bukan iframe/stranger)
 * 3. hanya payload bertipe 'google-oauth'
 */

const POPUP_W = 480
const POPUP_H = 640

export interface GooglePopupHandlers {
  /** Kode berhasil diterima dari popup → tukar token di sini. */
  onCode: (code: string) => void | Promise<void>
  /** Google kembali dengan kesalahan (error | blocked | linked | conflict | token). */
  onError?: (kind: string) => void
  /** Popup ditutup user tanpa menyelesaikan. */
  onDismissed?: () => void
}

export function useGooglePopup() {
  let popup: Window | null = null
  let watch: number | undefined

  /** Pusat popup di atas jendela induk. */
  function centerSpec(): string {
    const dualLeft = window.screenLeft ?? window.screenX
    const dualTop = window.screenTop ?? window.screenY
    const w = window.innerWidth || document.documentElement.clientWidth || screen.width
    const h = window.innerHeight || document.documentElement.clientHeight || screen.height
    const left = Math.max(0, Math.round(dualLeft + (w - POPUP_W) / 2))
    const top = Math.max(0, Math.round(dualTop + (h - POPUP_H) / 2.5))
    return `popup=yes,width=${POPUP_W},height=${POPUP_H},left=${left},top=${top},scrollbars=yes,status=no`
  }

  function onMessage(event: MessageEvent, handlers: GooglePopupHandlers) {
    if (event.origin !== window.location.origin) return
    if (event.source !== popup) return
    const data = event.data as { type?: string; code?: string; error?: string } | null
    if (!data || data.type !== 'google-oauth') return

    cleanup()
    popup?.close()

    if (data.error) {
      handlers.onError?.(data.error)
      return
    }
    if (data.code) {
      void handlers.onCode(data.code)
    }
  }

  function cleanup() {
    window.removeEventListener('message', onMessageRef)
    if (watch) {
      window.clearInterval(watch)
      watch = undefined
    }
  }

  // Wrapper agar listener bisa direferensikan untuk removeEventListener
  let currentHandlers: GooglePopupHandlers | null = null
  const onMessageRef = (event: MessageEvent) => {
    if (currentHandlers) onMessage(event, currentHandlers)
  }

  /**
   * Buka popup OAuth Google.
   * @param backendUrl URL redirect backend (dengan intent/token bila perlu)
   */
  function open(backendUrl: string, handlers: GooglePopupHandlers): boolean {
    cleanup()

    popup = window.open(backendUrl, 'google_oauth', centerSpec())

    // Popup diblokir → kembalikan false; pemanggil fallback ke redirect penuh
    if (!popup) return false

    currentHandlers = handlers
    window.addEventListener('message', onMessageRef)

    watch = window.setInterval(() => {
      if (popup?.closed) {
        cleanup()
        handlers.onDismissed?.()
      }
    }, 500)

    return true
  }

  /** Fallback redirect penuh (popup diblokir / gagal dibuka). */
  function fallbackRedirect(backendUrl: string) {
    window.location.href = backendUrl
  }

  /** URL callback SPA untuk mode popup. */
  function spaCallbackUrl(): string {
    return `${window.location.origin}/auth/google/callback?popup=1`
  }

  /** URL langkah-1 OAuth (mode login) — langsung ke backend. */
  function loginUrl(): string {
    const landing = spaCallbackUrl()
    return `${BACKEND_URL}/auth/google/redirect?redirect=${encodeURIComponent(landing)}`
  }

  onBeforeUnmount(() => {
    cleanup()
    popup?.close()
  })

  return { open, fallbackRedirect, loginUrl, spaCallbackUrl }
}
