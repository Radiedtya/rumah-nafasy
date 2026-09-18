import { ref, computed } from 'vue'

export type ThemeMode = 'system' | 'light' | 'dark'

function getStoredMode(): ThemeMode {
  if (typeof window === 'undefined') return 'system'
  const saved = localStorage.getItem('rn.theme.mode')
  if (saved === 'light' || saved === 'dark' || saved === 'system') return saved
  // Legacy key dari versi toggle lama
  const legacy = localStorage.getItem('rn.theme')
  if (legacy === 'dark' || legacy === 'light') return legacy
  return 'system'
}

function systemPrefersDark(): boolean {
  if (typeof window === 'undefined') return false
  return window.matchMedia('(prefers-color-scheme: dark)').matches
}

const mode = ref<ThemeMode>(getStoredMode())
const systemDark = ref(systemPrefersDark())

// Ikuti perubahan preferensi OS secara realtime
if (typeof window !== 'undefined') {
  window
    .matchMedia('(prefers-color-scheme: dark)')
    .addEventListener('change', (e) => {
      systemDark.value = e.matches
    })
}

function applyTheme() {
  if (typeof window === 'undefined') return
  const resolved = resolvedMode.value
  document.documentElement.dataset.theme = resolved
  localStorage.setItem('rn.theme.mode', mode.value)
}

const resolvedMode = computed<ThemeMode>(() =>
  mode.value === 'system' ? (systemDark.value ? 'dark' : 'light') : mode.value,
)

function setMode(next: ThemeMode) {
  mode.value = next
  applyTheme()
}

// Terapkan tema awal saat modul dimuat di browser
if (typeof window !== 'undefined') {
  applyTheme()
}

export function useTheme() {
  return {
    mode,
    resolvedMode,
    setMode,
  }
}
