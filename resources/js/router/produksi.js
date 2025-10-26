import { createRouter, createWebHistory } from 'vue-router'
import LoginProduksi from '../components/produksi/LoginProduksi.vue'

const routes = [
  {
    path: '/produksi/login',
    name: 'produksi-login',
    component: LoginProduksi,
    meta: { guest: true }
  },

  {
    path: '/produksi/staff',
    component: () => import('../components/produksi/staff/Layout.vue'),
    meta: { 
      requiresAuth: true,
      role: 'staff'
    },
    children: [
      {
        path: 'dashboard',
        name: 'produksi-staff-dashboard',
        component: () => import('../components/produksi/staff/Dashboard.vue'),
      },
      {
        path: 'quality-control',
        name: 'produksi-staff-quality-control',
        component: () => import('../components/produksi/staff/QualityControl.vue'),
      },
      {
        path: 'logistics',
        name: 'produksi-staff-logistics',
        component: () => import('../components/produksi/staff/Logistics.vue'),
      },
      {
        path: 'profile',
        name: 'produksi-staff-profile',
        component: () => import('../components/produksi/staff/Profile.vue'),
      },
        ]
  },

  {
    path: '/produksi/manager',
    component: () => import('../components/produksi/manager/Layout.vue'),
    meta: { 
      requiresAuth: true,
      role: 'manager'
    },
    children: [
      {
        path: 'dashboard',
        name: 'produksi-manager-dashboard',
        component: () => import('../components/produksi/manager/Dashboard.vue'),
      },
      {
        path: 'profile',
        name: 'produksi-manager-profile',
        component: () => import('../components/produksi/manager/Profile.vue'),
      },
      {
        path: 'reports',
        name: 'produksi-manager-reports',
        component: () => import('../components/produksi/manager/Reports.vue'),
      },
    ]
  },

  {
    path: '/produksi/:pathMatch(.*)*',
    redirect: '/produksi/login'
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
        name: 'produksi-login',
        query: { redirect: to.fullPath }
      })
    } else {
      if (to.meta.role && user.role !== to.meta.role) {
        next({ name: 'produksi-login' })
      } else {
        next()
      }
    }
  } else if (to.matched.some(record => record.meta.guest)) {
    if (token) {
      if (user.role === 'manager') {
        next({ name: 'produksi-manager-dashboard' })
      } else {
        next({ name: 'produksi-staff-dashboard' })
      }
    } else {
      next()
    }
  } else {
    next()
  }
})

export default router