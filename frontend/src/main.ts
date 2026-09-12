import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { ArrowUpRightIcon, MoonIcon, SunIcon } from '@heroicons/vue/24/outline'
import { siBluesky, siInstagram, siTiktok, siX } from 'simple-icons'
import router from './router'
import './style.css'
import App from './App.vue'
import SimpleIcon from './components/SimpleIcon.vue'

const app = createApp(App)

app
  .component('ArrowUpRightIcon', ArrowUpRightIcon)
  .component('MoonIcon', MoonIcon)
  .component('SunIcon', SunIcon)
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
  .use(createPinia())
  .use(router)
  .mount('#app')
