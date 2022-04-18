import { createRouter, createWebHistory } from 'vue-router';
import HomeView from '../views/HomeView.vue';

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/manage-maps',
      name: 'maps',
      component: () => import('../views/ManageMapsView.vue'),
    },
    {
      path: '/map/edit/:id',
      name: 'map-edit',
      component: () => import('../views/MapEditView.vue'),
    },
    {
      path: '/manage-tilesets',
      name: 'tilesets',
      component: () => import('../views/ManageTilesetsView.vue'),
    },
  ],
});

export default router;
