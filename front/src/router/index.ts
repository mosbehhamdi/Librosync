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
  }
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  const isAuthenticated = authStore.isAuthenticated();

  console.log('Navigation guard:', {
    to: to.path,
    isAuthenticated,
    user: authStore.user
  });

  // Handle guest routes (login, register)
  if (to.meta.requiresGuest) {
    if (isAuthenticated) {
      // If authenticated, check verification status
      const isVerified = authStore.isVerified();
      return next(isVerified ? '/dashboard' : '/verify-email');
    }
    return next(); // Allow access to guest routes if not authenticated
  }

  // Handle verification route
  if (to.name === 'verification.verify') {
    return next(); // Always allow access to verification link
  }

  // Handle protected routes
  if (to.meta.requiresAuth) {
    if (!isAuthenticated) {
      return next('/login');
    }

    const isVerified = authStore.isVerified();
    if (to.meta.requiresVerification && !isVerified) {
      return next('/verify-email');
    }
  }

  next();
});

export default router;
