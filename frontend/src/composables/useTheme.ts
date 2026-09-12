import { ref } from 'vue'

type Theme = 'light' | 'dark'
const STORAGE_KEY = 'rumah-natasy-theme'
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

  const savedTheme = localStorage.getItem(STORAGE_KEY)

  const initialTheme: Theme =
    savedTheme === 'dark' || savedTheme === 'light'
      ? savedTheme
      : 'light'
  applyTheme(initialTheme)
}

const toggleTheme = () => {
  applyTheme(theme.value === 'light' ? 'dark' : 'light')
}

export const useTheme = () => ({
  theme,
  initializeTheme,
  toggleTheme,
})