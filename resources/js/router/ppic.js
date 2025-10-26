import { createRouter, createWebHistory } from 'vue-router'
import LoginPPIC from '../components/ppic/LoginPPIC.vue'

const routes = [
  {
    path: '/ppic/login',
    name: 'ppic-login',
    component: LoginPPIC,
    meta: { guest: true }
  },

  {
    path: '/ppic/staff',
    component: () => import('../components/ppic/staff/Layout.vue'),
    meta: { 
      requiresAuth: true,
      role: 'staff'
    },
    children: [
      {
        path: 'dashboard',
        name: 'ppic-staff-dashboard',
        component: () => import('../components/ppic/staff/Dashboard.vue'),
      },
      {
        path: 'profile',
        name: 'ppic-staff-profile',
        component: () => import('../components/ppic/staff/Profile.vue'),
      },
    ]
  },

  {
    path: '/ppic/manager',
    component: () => import('../components/ppic/manager/Layout.vue'),
    meta: { 
      requiresAuth: true,
      role: 'manager'
    },
    children: [
      {
        path: 'dashboard',
        name: 'ppic-manager-dashboard',
        component: () => import('../components/ppic/manager/Dashboard.vue'),
      },
      {
        path: 'profile',
        name: 'ppic-manager-profile',
        component: () => import('../components/ppic/manager/Profile.vue'),
      },
      {
        path: 'reports',
        name: 'ppic-manager-reports',
        component: () => import('../components/ppic/manager/Reports.vue'),
      },
    ]
  },

  {
    path: '/ppic/:pathMatch(.*)*',
    redirect: '/ppic/login'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user') || '{}')

  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!token) {
      next({
        name: 'ppic-login',
        query: { redirect: to.fullPath }
      })
    } else {
      if (to.meta.role && user.role !== to.meta.role) {
        next({ name: 'ppic-login' })
      } else {
        next()
      }
    }
  } else if (to.matched.some(record => record.meta.guest)) {
    if (token) {
      if (user.role === 'manager') {
        next({ name: 'ppic-manager-dashboard' })
      } else {
        next({ name: 'ppic-staff-dashboard' })
      }
    } else {
      next()
    }
  } else {
    next()
  }
})

export default router