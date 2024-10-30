import { createRouter, createWebHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: to => {
      const authStore = useAuthStore();
      return authStore.user?.is_admin ? '/admin/dashboard' : '/books';
    }
  },
  // Routes publiques
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginPage.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/RegisterPage.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/verify-email',
    name: 'verify-email',
    component: () => import('@/views/VerificationPendingPage.vue'),
    meta: { 
      requiresAuth: true,
      requiresVerification: false 
    }
  },
  {
    path: '/email/verify/:id/:hash',
    name: 'verification.verify',
    component: () => import('@/views/EmailVerificationPage.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/ForgotPasswordPage.vue'),
    meta: { requiresGuest: true }
  },
  
  // Routes utilisateur
  {
    path: '/books',
    name: 'books',
    component: () => import('@/views/BooksPage.vue'),
    meta: { 
      requiresAuth: true,
      requiresVerification: true 
    }
  },
  {
    path: '/my-reservations',
    name: 'my-reservations',
    component: () => import('@/views/MyReservationsPage.vue'),
    meta: { 
      requiresAuth: true,
      requiresVerification: true 
    }
  },
  {
    path: '/my-profile',
    name: 'my-profile',
    component: () => import('@/views/MyProfilePage.vue'),
    meta: { 
      requiresAuth: true,
      requiresVerification: true 
    }
  },

  // Routes admin
  {
    path: '/admin',
    redirect: '/admin/dashboard',
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/dashboard',
    name: 'admin-dashboard',
    component: () => import('@/views/admin/DashboardPage.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/users',
    name: 'admin-users',
    component: () => import('@/views/admin/UsersPage.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/books',
    name: 'admin-books',
    component: () => import('@/views/admin/BooksPage.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/reservations',
    name: 'admin-reservations',
    component: () => import('@/views/admin/ReservationsPage.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },

  // Route par défaut
  {
    path: '/:pathMatch(.*)*',
    redirect: to => {
      const authStore = useAuthStore();
      return authStore.isAuthenticated ? '/books' : '/login';
    }
  }
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  const isAuthenticated = authStore.isAuthenticated;

  if (to.meta.requiresGuest) {
    if (isAuthenticated) {
      return next(authStore.user?.is_admin ? '/admin/dashboard' : '/books');
    }
    return next();
  }

  if (to.meta.requiresAuth && !isAuthenticated) {
    return next('/login');
  }

  const isVerified = authStore.isVerified;
  if (to.meta.requiresVerification && !isVerified) {
    return next('/verify-email');
  }

  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    return next('/books');
  }

  next();
});

export default router;
