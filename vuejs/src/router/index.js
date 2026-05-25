import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import Home from '../views/Home.vue';
import AdminList from '../views/AdminList.vue';
import UserList from '../views/UserList.vue';
import DiscoverGames from '../views/DiscoverGames.vue';
import GameDetail from '../views/GameDetail.vue';
import UserProfile from '../views/UserProfile.vue';
import ManageGame from '../views/ManageGame.vue';

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { guest: true }
  },
  {
    path: '/',
    name: 'Home',
    component: Home,
    meta: { requiresAuth: true }
  },
  {
    path: '/admins',
    name: 'AdminList',
    component: AdminList,
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/users',
    name: 'UserList',
    component: UserList,
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/games',
    name: 'DiscoverGames',
    component: DiscoverGames,
    meta: { requiresAuth: true }
  },
  {
    path: '/games/:slug',
    name: 'GameDetail',
    component: GameDetail,
    meta: { requiresAuth: true }
  },
  {
    path: '/games/:slug/manage',
    name: 'ManageGame',
    component: ManageGame,
    meta: { requiresAuth: true }
  },
  {
    path: '/profile/:username',
    name: 'UserProfile',
    component: UserProfile,
    meta: { requiresAuth: true }
  },
  {
    
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});


router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token');
  const userJson = localStorage.getItem('user');
  const user = userJson ? JSON.parse(userJson) : null;

  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!token) {
      
      next({ name: 'Login' });
    } else if (to.matched.some(record => record.meta.requiresAdmin)) {
      if (user && user.role === 'admin') {
        next();
      } else {
        
        next({ name: 'Home' });
      }
    } else {
      next();
    }
  } else if (to.matched.some(record => record.meta.guest)) {
    if (token) {
      
      next({ name: 'Home' });
    } else {
      next();
    }
  } else {
    next();
  }
});

export default router;
