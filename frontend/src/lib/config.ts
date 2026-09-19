/**
 * URL backend untuk alur yang butuh full-page navigation ke Laravel
 * (bukan fetch API). Fetch API memakai proxy Vite (`/api/v1`), tapi
 * redirect OAuth Google harus menuju origin backend langsung.
 *
 * Set `VITE_BACKEND_URL` di frontend/.env bila backend berjalan di
 * origin lain; default dev: http://localhost:8000
 */
export const BACKEND_URL: string =
  (import.meta.env.VITE_BACKEND_URL as string | undefined)?.replace(/\/$/, '') ||
  'http://localhost:8000'

/** URL langkah-1 OAuth Google (dibuka sebagai navigasi penuh browser). */
export function googleAuthUrl(spaCallbackUrl?: string): string {
  const base = `${BACKEND_URL}/auth/google/redirect`
  return spaCallbackUrl ? `${base}?redirect=${encodeURIComponent(spaCallbackUrl)}` : base
}
