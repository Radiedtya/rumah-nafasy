/**
 * Lightweight API client for Rumah Nafasy Laravel Backend
 * Zero heavy dependencies — native fetch with typed responses
 */

/** Bentuk `meta` dari paginateResponse() (trait ApiResponse di backend Laravel) */
export interface ApiMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
  has_more: boolean
}

export interface ApiResponse<T = any> {
  success: boolean
  message: string
  data: T
  meta?: ApiMeta
  errors?: Record<string, string[]>
}

export class ApiError extends Error {
  status: number
  errors?: Record<string, string[]>

  constructor(message: string, status: number, errors?: Record<string, string[]>) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }
}

export async function apiFetch<T = any>(
  endpoint: string,
  options: RequestInit = {}
): Promise<ApiResponse<T>> {
  const token = typeof window !== 'undefined' ? localStorage.getItem('token') : null

  const headers: Record<string, string> = {
    Accept: 'application/json',
    ...(options.headers as Record<string, string>),
  }

  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  if (options.body && !(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json'
  }

  const url = endpoint.startsWith('http')
    ? endpoint
    : `/api/v1${endpoint.startsWith('/') ? '' : '/'}${endpoint}`

  try {
    const res = await fetch(url, {
      ...options,
      headers,
    })

    const data = await res.json().catch(() => ({}))

    if (!res.ok) {
      if (res.status === 401 && typeof window !== 'undefined') {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
      }
      throw new ApiError(
        data.message || `Request gagal dengan status ${res.status}`,
        res.status,
        data.errors
      )
    }

    return data as ApiResponse<T>
  } catch (err: any) {
    if (err instanceof ApiError) {
      throw err
    }
    throw new ApiError(err.message || 'Koneksi ke server gagal', 0)
  }
}
