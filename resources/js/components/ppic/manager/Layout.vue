`<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="shrink-0 flex items-center">
              <h1 class="text-xl font-bold">Manager PPIC</h1>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <router-link 
                to="/ppic/manager/dashboard"
                class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                active-class="border-indigo-500"
                inactive-class="border-transparent"
              >
                Dashboard
              </router-link>
              <router-link 
                to="/ppic/manager/reports"
                class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                active-class="border-indigo-500"
                inactive-class="border-transparent"
              >
                Reports
              </router-link>
              <router-link 
                to="/ppic/manager/profile"
                class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                active-class="border-indigo-500"
                inactive-class="border-transparent"
              >
                Profile
              </router-link>
            </div>
          </div>
          <div class="flex items-center">
            <button
              @click="logout"
              class="ml-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Page Content -->
    <main>
      <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <router-view></router-view>
      </div>
    </main>
  </div>
</template>

<script>
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'ManagerLayout',
  setup() {
    const router = useRouter()

    const logout = async () => {
      try {
        await axios.post('/ppic/logout')
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        router.push('/ppic/login')
      } catch (error) {
        console.error('Logout failed:', error)
      }
    }

    return {
      logout
    }
  }
}
</script>`