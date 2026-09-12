import { ViteSSG } from 'vite-ssg'
import { createPinia } from 'pinia'
import {
  ArrowUpRightIcon,
  CalendarDaysIcon,
  DevicePhoneMobileIcon,
  MoonIcon,
  SunIcon,
  UserGroupIcon,
  ShieldCheckIcon,
  CurrencyDollarIcon,
  HeartIcon,
  StarIcon,
  ChatBubbleLeftRightIcon,
  BellAlertIcon,
  VideoCameraIcon,
  CalendarIcon,
  CheckBadgeIcon,
  LockClosedIcon,
} from '@heroicons/vue/24/outline'
import { siBluesky, siInstagram, siTiktok, siX } from 'simple-icons'
import { routes } from './router'
import './style.css'
import App from './App.vue'
import SimpleIcon from './components/SimpleIcon.vue'

export const createApp = ViteSSG(App, { routes }, ({ app }) => {
  const pinia = createPinia()

  app
    .component('ArrowUpRightIcon', ArrowUpRightIcon)
    .component('MoonIcon', MoonIcon)
    .component('SunIcon', SunIcon)
    .component('CalendarDaysIcon', CalendarDaysIcon)
    .component('CalendarIcon', CalendarIcon)
    .component('DevicePhoneMobileIcon', DevicePhoneMobileIcon)
    .component('UserGroupIcon', UserGroupIcon)
    .component('ShieldCheckIcon', ShieldCheckIcon)
    .component('CurrencyDollarIcon', CurrencyDollarIcon)
    .component('HeartIcon', HeartIcon)
    .component('StarIcon', StarIcon)
    .component('ChatBubbleLeftRightIcon', ChatBubbleLeftRightIcon)
    .component('BellAlertIcon', BellAlertIcon)
    .component('VideoCameraIcon', VideoCameraIcon)
    .component('CheckBadgeIcon', CheckBadgeIcon)
    .component('LockClosedIcon', LockClosedIcon)
    .component('SimpleBlueskyIcon', {
      extends: SimpleIcon,
      props: { icon: { default: () => siBluesky } },
    })
    .component('SimpleInstagramIcon', {
      extends: SimpleIcon,
      props: { icon: { default: () => siInstagram } },
    })
    .component('SimpleTiktokIcon', {
      extends: SimpleIcon,
      props: { icon: { default: () => siTiktok } },
    })
    .component('SimpleXIcon', {
      extends: SimpleIcon,
      props: { icon: { default: () => siX } },
    })
    .use(pinia)
})
