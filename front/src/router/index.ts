import { createRouter, createWebHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import ExpiredReservationsPage from '@/views/user/ExpiredReservationsPage.vue';
import UserReservationHistoryPage from '@/views/user/ReservationHistoryPage.vue';
import MyReservationsPage from '@/views/user/MyReservationsPage.vue';
import AdminReservationsPage from '@/views/admin/ReservationsPage.vue';

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: to => {
      const authStore = useAuthStore();
      return authStore.user?.is_admin ? '/admin/dashboard' : '/books';
    }
  },
  // Public routes
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginPage.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/user/RegisterPage.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/verify-email',
    name: 'verify-email',
    component: () => import('@/views/user/VerificationPendingPage.vue'),
    meta: { requiresAuth: true, requiresVerification: false }
  },
  {
    path: '/email/verify/:id/:hash',
    name: 'verification.verify',
    component: () => import('@/views/user/EmailVerificationPage.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/user/ForgotPasswordPage.vue'),
    meta: { requiresGuest: true }
  },
  
  // User routes
  {
    path: '/books',
    name: 'books',
    component: () => import('@/views/user/BooksPage.vue'),
    meta: { requiresAuth: true, requiresVerification: true }
  },
  {
    path: '/my-reservations',
    name: 'my-reservations',
    component: MyReservationsPage,
    meta: { requiresAuth: true, requiresVerification: true }
  },
  {
    path: '/my-profile',
    name: 'my-profile',
    component: () => import('@/views/user/MyProfilePage.vue'),
    meta: { requiresAuth: true, requiresVerification: true }
  },
  {
    path: '/user/reservations/history',
    name: 'user-reservation-history',
    component: UserReservationHistoryPage,
    meta: { requiresAuth: true }
  },
  {
    path: '/reservations/expired',
    name: 'expired-reservations',
    component: ExpiredReservationsPage,
    meta: { requiresAuth: true, requiresVerification: true }
  },

  // Admin routes
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
    component: AdminReservationsPage,
    meta: { requiresAuth: true, requiresAdmin: true }
  },

  // Default route
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
