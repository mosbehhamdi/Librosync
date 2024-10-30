import { createRouter, createWebHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/login'
  },
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
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/DashboardPage.vue'),
    meta: { 
      requiresAuth: true,
      requiresVerification: true 
    }
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/ForgotPasswordPage.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login'
  },
  {
    path: '/admin',
    redirect: '/admin/dashboard',
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/dashboard',
    component: () => import('@/views/admin/DashboardPage.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/users',
    component: () => import('@/views/admin/UsersPage.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  }
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  const isAuthenticated = authStore.isAuthenticated();

  // Handle guest routes (login, register)
  if (to.meta.requiresGuest) {
    if (isAuthenticated) {
      // Redirect based on user role
      return next(authStore.user?.is_admin ? '/admin/dashboard' : '/dashboard');
    }
    return next();
  }

  // Handle protected routes
  if (to.meta.requiresAuth) {
    if (!isAuthenticated) {
      return next('/login');
    }

    // Check verification status
    const isVerified = authStore.isVerified();
    if (to.meta.requiresVerification && !isVerified) {
      return next('/verify-email');
    }

    // Handle admin routes
    if (to.meta.requiresAdmin) {
      if (!authStore.user?.is_admin) {
        return next('/dashboard');
      }
    } else if (authStore.user?.is_admin && to.path === '/dashboard') {
      // Redirect admin to admin dashboard if trying to access user dashboard
      return next('/admin/dashboard');
    }
  }

  next();
});

export default router;
