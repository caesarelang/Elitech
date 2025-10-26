<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="shrink-0">
              <div class="rounded-md bg-blue-500 p-3">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Approved Plans</dt>
                <dd class="text-lg font-semibold text-gray-900">{{ stats.approved }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="shrink-0">
              <div class="rounded-md bg-yellow-500 p-3">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
                <dd class="text-lg font-semibold text-gray-900">{{ stats.in_progress }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="shrink-0">
              <div class="rounded-md bg-green-500 p-3">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Completed Today</dt>
                <dd class="text-lg font-semibold text-gray-900">{{ stats.completed_today }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="shrink-0">
              <div class="rounded-md bg-red-500 p-3">
                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Overdue</dt>
                <dd class="text-lg font-semibold text-gray-900">{{ stats.overdue }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white shadow rounded-lg">
      <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Production Orders</h3>
        <p class="mt-1 text-sm text-gray-500">Manage your production tasks</p>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        <p class="mt-2 text-sm text-gray-500">Loading production plans...</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="plan in plans" :key="plan.id">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ plan.product?.name }}</div>
                <div class="text-sm text-gray-500">{{ plan.product?.sku }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ plan.quantity }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getPriorityClass(plan.priority)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                  {{ plan.priority_label }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDate(plan.deadline) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusClass(plan.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                  {{ plan.status_label }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                    <div :class="getProgressColor(plan.progress_percentage)" class="h-2 rounded-full" :style="{ width: plan.progress_percentage + '%' }"></div>
                  </div>
                  <span class="text-sm text-gray-900">{{ plan.progress_percentage }}%</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button
                  v-if="plan.status === 'approved'"
                  @click="startProduction(plan)"
                  class="text-indigo-600 hover:text-indigo-900 mr-3"
                >
                  Start
                </button>
                <button
                  v-if="plan.status === 'in_progress'"
                  @click="openProgressModal(plan)"
                  class="text-blue-600 hover:text-blue-900 mr-3"
                >
                  Update
                </button>
                <button
                  v-if="plan.status === 'in_progress' && plan.progress_percentage === 100"
                  @click="openCompleteModal(plan)"
                  class="text-green-600 hover:text-green-900"
                >
                  Complete
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="plans.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No production plans</h3>
          <p class="mt-1 text-sm text-gray-500">There are no approved production plans at the moment.</p>
        </div>
      </div>

      <div v-if="pagination.total > pagination.per_page" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <div class="flex-1 flex justify-between sm:hidden">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            Previous
          </button>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            Next
          </button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Showing <span class="font-medium">{{ pagination.from }}</span> to <span class="font-medium">{{ pagination.to }}</span> of <span class="font-medium">{{ pagination.total }}</span> results
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
              <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="pagination.current_page === 1"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
              >
                <span class="sr-only">Previous</span>
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </button>
              <button
                v-for="page in visiblePages"
                :key="page"
                @click="changePage(page)"
                :class="[
                  page === pagination.current_page
                    ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                ]"
              >
                {{ page }}
              </button>
              <button
                @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
              >
                <span class="sr-only">Next</span>
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showProgressModal" class="fixed z-10 inset-0 overflow-y-auto">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showProgressModal = false"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Update Progress</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Progress Percentage</label>
                <input
                  v-model.number="progressForm.progress_percentage"
                  type="number"
                  min="0"
                  max="100"
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Progress Notes (Optional)</label>
                <textarea
                  v-model="progressForm.progress_notes"
                  rows="3"
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                ></textarea>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              @click="updateProgress"
              :disabled="submitting"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
            >
              {{ submitting ? 'Updating...' : 'Update Progress' }}
            </button>
            <button
              @click="showProgressModal = false"
              :disabled="submitting"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showCompleteModal" class="fixed z-10 inset-0 overflow-y-auto">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCompleteModal = false"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Complete Production</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Produced Quantity *</label>
                <input
                  v-model.number="completeForm.produced_quantity"
                  type="number"
                  min="1"
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Completion Notes (Optional)</label>
                <textarea
                  v-model="completeForm.completion_notes"
                  rows="3"
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                ></textarea>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              @click="completeProduction"
              :disabled="submitting || !completeForm.produced_quantity"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
            >
              {{ submitting ? 'Completing...' : 'Complete Production' }}
            </button>
            <button
              @click="showCompleteModal = false"
              :disabled="submitting"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

export default {
  name: 'StaffProductionDashboard',
  setup() {
    const loading = ref(false)
    const submitting = ref(false)
    const plans = ref([])
    const stats = ref({
      approved: 0,
      in_progress: 0,
      completed_today: 0,
      overdue: 0
    })
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: 0,
      to: 0
    })

    const showProgressModal = ref(false)
    const showCompleteModal = ref(false)
    const selectedPlan = ref(null)
    const progressForm = ref({
      progress_percentage: 0,
      progress_notes: ''
    })
    const completeForm = ref({
      produced_quantity: 0,
      completion_notes: ''
    })

    const visiblePages = computed(() => {
      const pages = []
      const total = pagination.value.last_page
      const current = pagination.value.current_page
      const delta = 2

      for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
        pages.push(i)
      }

      if (current - delta > 2) {
        pages.unshift('...')
      }
      if (current + delta < total - 1) {
        pages.push('...')
      }

      pages.unshift(1)
      if (total > 1) {
        pages.push(total)
      }

      return pages.filter((v, i, a) => a.indexOf(v) === i)
    })

    const fetchStats = async () => {
      try {
        const response = await axios.get('/produksi/dashboard-stats')
        stats.value = response.data
      } catch (error) {
        console.error('Error fetching stats:', error)
      }
    }

    const fetchPlans = async (page = 1) => {
      loading.value = true
      try {
        const response = await axios.get('/produksi/production-plans', {
          params: { page, per_page: 15 }
        })
        plans.value = response.data.data
        pagination.value = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total,
          from: response.data.from,
          to: response.data.to
        }
      } catch (error) {
        console.error('Error fetching plans:', error)
      } finally {
        loading.value = false
      }
    }

    const startProduction = async (plan) => {
      if (!confirm('Are you sure you want to start this production?')) return

      try {
        await axios.post(`/produksi/production-plans/${plan.id}/start`)
        alert('Production started successfully!')
        await fetchPlans(pagination.value.current_page)
        await fetchStats()
      } catch (error) {
        alert(error.response?.data?.message || 'Failed to start production')
      }
    }

    const openProgressModal = (plan) => {
      selectedPlan.value = plan
      progressForm.value = {
        progress_percentage: plan.progress_percentage || 0,
        progress_notes: ''
      }
      showProgressModal.value = true
    }

    const updateProgress = async () => {
      if (!selectedPlan.value) return
      
      submitting.value = true
      try {
        await axios.put(
          `/produksi/production-plans/${selectedPlan.value.id}/progress`,
          progressForm.value
        )
        alert('Progress updated successfully!')
        showProgressModal.value = false
        await fetchPlans(pagination.value.current_page)
        await fetchStats()
      } catch (error) {
        alert(error.response?.data?.message || 'Failed to update progress')
      } finally {
        submitting.value = false
      }
    }

    const openCompleteModal = (plan) => {
      selectedPlan.value = plan
      completeForm.value = {
        produced_quantity: plan.quantity,
        completion_notes: ''
      }
      showCompleteModal.value = true
    }

    const completeProduction = async () => {
      if (!selectedPlan.value) return
      
      submitting.value = true
      try {
        await axios.post(
          `/produksi/production-plans/${selectedPlan.value.id}/complete`,
          completeForm.value
        )
        alert('Production completed successfully!')
        showCompleteModal.value = false
        await fetchPlans(pagination.value.current_page)
        await fetchStats()
      } catch (error) {
        alert(error.response?.data?.message || 'Failed to complete production')
      } finally {
        submitting.value = false
      }
    }

    const changePage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        fetchPlans(page)
      }
    }

    const getPriorityClass = (priority) => {
      const classes = {
        urgent: 'bg-red-100 text-red-800',
        high: 'bg-orange-100 text-orange-800',
        medium: 'bg-yellow-100 text-yellow-800',
        low: 'bg-green-100 text-green-800'
      }
      return classes[priority] || 'bg-gray-100 text-gray-800'
    }

    const getStatusClass = (status) => {
      const classes = {
        approved: 'bg-blue-100 text-blue-800',
        in_progress: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-green-100 text-green-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const getProgressColor = (percentage) => {
      if (percentage >= 100) return 'bg-green-600'
      if (percentage >= 75) return 'bg-blue-600'
      if (percentage >= 50) return 'bg-yellow-600'
      return 'bg-red-600'
    }

    const formatDate = (date) => {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    onMounted(() => {
      fetchStats()
      fetchPlans()
    })

    return {
      loading,
      submitting,
      plans,
      stats,
      pagination,
      visiblePages,
      showProgressModal,
      showCompleteModal,
      progressForm,
      completeForm,
      startProduction,
      openProgressModal,
      updateProgress,
      openCompleteModal,
      completeProduction,
      changePage,
      getPriorityClass,
      getStatusClass,
      getProgressColor,
      formatDate
    }
  }
}
</script>