import { createRouter, createWebHistory } from 'vue-router'
import PublicLayout from '../layouts/PublicLayout.vue'
import Home from '../pages/Home.vue'

const router = createRouter({
  history: createWebHistory(),

  routes: [
    {
      path: '/',
      component: PublicLayout,
      children: [
        {
          path: '',
          component: Home,
        },
      ],
    },
  ],
})

export default router
