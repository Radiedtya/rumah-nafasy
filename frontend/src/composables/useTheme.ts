import { ref } from 'vue'

type Theme = 'light' | 'dark'
const STORAGE_KEY = 'rumah-nafasy-theme'
const theme = ref<Theme>('light')

const applyTheme = (nextTheme: Theme) => {
  theme.value = nextTheme

  if (typeof document !== 'undefined') {
    document.documentElement.dataset.theme = nextTheme
  }

  if (typeof localStorage !== 'undefined') {
    localStorage.setItem(STORAGE_KEY, nextTheme)
  }
}

const initializeTheme = () => {
  if (typeof window === 'undefined') {
    return
  }

  const savedTheme = window.localStorage.getItem(STORAGE_KEY)
  const initialTheme: Theme =
    savedTheme === 'dark' || savedTheme === 'light'
      ? savedTheme
      : window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light'

  applyTheme(initialTheme)
}

if (typeof window !== 'undefined') {
  initializeTheme()
}

const toggleTheme = () => {
  applyTheme(theme.value === 'light' ? 'dark' : 'light')
}

export const useTheme = () => ({
  theme,
  initializeTheme,
  toggleTheme,
})