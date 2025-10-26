<template>
  <div class="min-h-screen bg-gray-50">


    <!-- Main Content -->
    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Welcome Section -->
        <div class="mb-6">
          <h2 class="text-2xl font-bold text-gray-900">Welcome, {{ user.name }}</h2>
          <p class="text-gray-600 mt-1">Overview rencana produksi dan status gudang</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
          <!-- Total Plans -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Total Rencana</p>
                <p class="text-2xl font-bold text-gray-900">{{ stats.totalPlans }}</p>
              </div>
              <div class="bg-blue-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Pending Approval -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Pending Approval</p>
                <p class="text-2xl font-bold text-yellow-600">{{ stats.pendingApproval }}</p>
              </div>
              <div class="bg-yellow-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Approved -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Approved</p>
                <p class="text-2xl font-bold text-green-600">{{ stats.approved }}</p>
              </div>
              <div class="bg-green-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Rejected -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Rejected</p>
                <p class="text-2xl font-bold text-red-600">{{ stats.rejected }}</p>
              </div>
              <div class="bg-red-100 p-3 rounded-full">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Warehouse Stats & Low Stock Alert -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          
          <!-- Warehouse Stats -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Status Gudang</h3>
              <button
                @click="fetchWarehouseData"
                class="text-blue-600 hover:text-blue-700 text-sm"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </button>
            </div>

            <div v-if="loadingWarehouse" class="text-center py-8 text-gray-500">
              Memuat data gudang...
            </div>

            <div v-else class="space-y-4">
              <div v-for="warehouse in warehouses" :key="warehouse.warehouse_name" 
                   class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                <div class="flex items-center justify-between mb-2">
                  <h4 class="font-medium text-gray-900">{{ warehouse.warehouse_name }}</h4>
                  <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">
                    {{ warehouse.products_count || 0 }} Produk
                  </span>
                </div>
                <p class="text-sm text-gray-600">{{ warehouse.warehouse_location || 'Lokasi tidak tersedia' }}</p>
              </div>

              <div v-if="warehouses.length === 0" class="text-center py-8 text-gray-500">
                Tidak ada data gudang
              </div>
            </div>
          </div>

          <!-- Low Stock Alert -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">
                <span class="flex items-center gap-2">
                  <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  Low Stock Alert
                </span>
              </h3>
              <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-bold rounded">
                {{ lowStockItems.length }} Items
              </span>
            </div>

            <div v-if="loadingLowStock" class="text-center py-8 text-gray-500">
              Memuat data...
            </div>

            <div v-else class="space-y-3 max-h-96 overflow-y-auto">
              <div v-for="item in lowStockItems" :key="item.id"
                   class="border-l-4 border-red-500 bg-red-50 p-3 rounded">
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <h4 class="font-medium text-gray-900 text-sm">{{ item.product?.name || 'Unknown' }}</h4>
                    <p class="text-xs text-gray-600 mt-1">SKU: {{ item.product?.sku || '-' }}</p>
                    <p class="text-xs text-gray-600">Gudang: {{ item.warehouse_name || '-' }}</p>
                  </div>
                  <div class="text-right ml-3">
                    <div class="text-lg font-bold text-red-600">{{ formatNumber(item.stock) }}</div>
                    <div class="text-xs text-gray-500">Min: {{ formatNumber(item.minimum_stock) }}</div>
                  </div>
                </div>
                <div class="mt-2 flex items-center gap-2">
                  <div class="flex-1 bg-gray-200 rounded-full h-2">
                    <div 
                      class="bg-red-600 h-2 rounded-full transition-all"
                      :style="{ width: calculateStockPercentage(item) + '%' }"
                    ></div>
                  </div>
                  <span class="text-xs font-medium text-red-600">
                    {{ calculateStockPercentage(item) }}%
                  </span>
                </div>
              </div>

              <div v-if="lowStockItems.length === 0" class="text-center py-8">
                <svg class="w-12 h-12 mx-auto text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-gray-600 font-medium">Semua stok aman!</p>
                <p class="text-sm text-gray-500">Tidak ada produk dengan stok rendah</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Daftar Stock Lengkap -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Daftar Stock Produk</h3>
            <button
              @click="fetchStockList"
              class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Refresh
            </button>
          </div>

          <div v-if="loadingStockList" class="text-center py-12 text-gray-500">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-3">Memuat daftar stock...</p>
          </div>

          <div v-else>
            <!-- Search Bar -->
            <div class="mb-4">
              <input
                v-model="searchQuery"
                @input="filterStockList"
                type="text"
                placeholder="Cari produk (nama atau SKU)..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <!-- Stock Table -->
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Produk
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      SKU
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Gudang
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Stock
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Min. Stock
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="stock in filteredStockList" :key="stock.id" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">{{ stock.product?.name || '-' }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-600">{{ stock.product?.sku || '-' }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">{{ stock.warehouse_name }}</div>
                      <div class="text-xs text-gray-500">{{ stock.warehouse_location || '-' }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                      <div class="text-sm font-semibold text-gray-900">{{ formatNumber(stock.stock) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                      <div class="text-sm text-gray-600">{{ formatNumber(stock.minimum_stock) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                      <span :class="[
                        'px-2 py-1 text-xs font-semibold rounded-full',
                        getStockStatusClass(stock)
                      ]">
                        {{ getStockStatusLabel(stock) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>

              <div v-if="filteredStockList.length === 0" class="text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p class="text-gray-600 font-medium">Tidak ada data stock</p>
                <p class="text-sm text-gray-500 mt-1">
                  {{ searchQuery ? 'Tidak ditemukan hasil pencarian' : 'Belum ada produk di gudang' }}
                </p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Toast Notification -->
    <div
      v-if="toast.show"
      :class="[
        'fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 transition-all duration-300',
        toast.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'
      ]"
    >
      <div class="flex items-center gap-3">
        <svg v-if="toast.type === 'success'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span>{{ toast.message }}</span>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'ManagerDashboard',
  setup() {
    const router = useRouter()
    const user = ref(JSON.parse(localStorage.getItem('user') || '{}'))
    const productionPlans = ref([])
    const warehouses = ref([])
    const lowStockItems = ref([])
    const stockList = ref([])
    const filteredStockList = ref([])
    const searchQuery = ref('')
    const loading = ref(true)
    const loadingWarehouse = ref(false)
    const loadingLowStock = ref(false)
    const loadingStockList = ref(false)

    const stats = reactive({
      totalPlans: 0,
      pendingApproval: 0,
      approved: 0,
      rejected: 0
    })

    const toast = reactive({
      show: false,
      message: '',
      type: 'success'
    })

    const showToast = (message, type = 'success') => {
      toast.message = message
      toast.type = type
      toast.show = true
      setTimeout(() => {
        toast.show = false
      }, 3000)
    }

    const fetchPlans = async () => {
      loading.value = true
      try {
        const res = await axios.get('/production-plans', {
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
        })
        const allPlans = res.data.data || res.data
        productionPlans.value = allPlans.filter(plan => plan.status !== 'draft')
        
        stats.totalPlans = productionPlans.value.length
        stats.pendingApproval = productionPlans.value.filter(p => p.status === 'pending_approval').length
        stats.approved = productionPlans.value.filter(p => p.status === 'approved').length
        stats.rejected = productionPlans.value.filter(p => p.status === 'rejected').length
      } catch (error) {
        console.error('Gagal memuat data:', error.response?.data || error)
        showToast('Gagal memuat data rencana produksi', 'error')
      } finally {
        loading.value = false
      }
    }

    const fetchWarehouseData = async () => {
      loadingWarehouse.value = true
      try {
        const res = await axios.get('/produksi/logistics/warehouses', {
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
        })
        const data = res.data.data || res.data || []
        
        const warehouseMap = new Map()
        data.forEach(item => {
          if (!warehouseMap.has(item.warehouse_name)) {
            warehouseMap.set(item.warehouse_name, {
              warehouse_name: item.warehouse_name,
              warehouse_location: item.warehouse_location,
              products_count: 0
            })
          }
          warehouseMap.get(item.warehouse_name).products_count++
        })
        
        warehouses.value = Array.from(warehouseMap.values())
      } catch (error) {
        console.error('Gagal memuat data gudang:', error.response?.data || error)
        showToast('Gagal memuat data gudang', 'error')
      } finally {
        loadingWarehouse.value = false
      }
    }

    const fetchLowStockAlert = async () => {
      loadingLowStock.value = true
      try {
        const res = await axios.get('/produksi/logistics/low-stock-alert', {
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
        })
        lowStockItems.value = res.data.data || res.data || []
      } catch (error) {
        console.error('Gagal memuat low stock alert:', error.response?.data || error)
      } finally {
        loadingLowStock.value = false
      }
    }

    const fetchStockList = async () => {
      loadingStockList.value = true
      try {
        const res = await axios.get('/produksi/logistics', {
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
        })
        stockList.value = res.data.data || res.data || []
        filteredStockList.value = stockList.value
      } catch (error) {
        console.error('Gagal memuat daftar stock:', error.response?.data || error)
        showToast('Gagal memuat daftar stock', 'error')
      } finally {
        loadingStockList.value = false
      }
    }

    const filterStockList = () => {
      const query = searchQuery.value.toLowerCase()
      if (!query) {
        filteredStockList.value = stockList.value
        return
      }
      
      filteredStockList.value = stockList.value.filter(stock => {
        const productName = stock.product?.name?.toLowerCase() || ''
        const productSku = stock.product?.sku?.toLowerCase() || ''
        return productName.includes(query) || productSku.includes(query)
      })
    }

    const formatNumber = (num) => {
      return new Intl.NumberFormat('id-ID').format(num || 0)
    }

    const calculateStockPercentage = (item) => {
      if (!item.minimum_stock || item.minimum_stock === 0) return 0
      return Math.min(100, Math.round((item.stock / item.minimum_stock) * 100))
    }

    const getStockStatusClass = (stock) => {
      if (stock.stock === 0) return 'bg-gray-200 text-gray-800'
      if (stock.stock < stock.minimum_stock) return 'bg-red-100 text-red-800'
      if (stock.stock < stock.minimum_stock * 1.5) return 'bg-yellow-100 text-yellow-800'
      return 'bg-green-100 text-green-800'
    }

    const getStockStatusLabel = (stock) => {
      if (stock.stock === 0) return 'Habis'
      if (stock.stock < stock.minimum_stock) return 'Low Stock'
      if (stock.stock < stock.minimum_stock * 1.5) return 'Warning'
      return 'Aman'
    }

    const logout = async () => {
      try {
        await axios.post('/ppic/logout', {}, {
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
        })
      } catch (error) {
        console.warn('Logout error:', error.response?.data || error)
      } finally {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        router.push('/ppic/login')
      }
    }

    onMounted(() => {
      if (user.value.role !== 'manager') {
        router.push('/ppic/login')
      } else {
        fetchPlans()
        fetchWarehouseData()
        fetchLowStockAlert()
        fetchStockList()
      }
    })

    return {
      user,
      logout,
      warehouses,
      lowStockItems,
      stockList,
      filteredStockList,
      searchQuery,
      stats,
      loading,
      loadingWarehouse,
      loadingLowStock,
      loadingStockList,
      toast,
      fetchWarehouseData,
      fetchStockList,
      filterStockList,
      formatNumber,
      calculateStockPercentage,
      getStockStatusClass,
      getStockStatusLabel
    }
  },
}
</script>