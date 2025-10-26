<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Laporan Produksi</h1>
      <p class="text-gray-600 mt-2">Kelola dan cetak laporan hasil produksi</p>
    </div>

    <!-- Filter & Generate Report Card -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">Generate Laporan Baru</h2>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <!-- Report Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Tipe Laporan
          </label>
          <select
            v-model="reportForm.report_type"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="weekly">Mingguan</option>
            <option value="monthly">Bulanan</option>
          </select>
        </div>

        <!-- Start Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Tanggal Mulai
          </label>
          <input
            type="date"
            v-model="reportForm.start_date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <!-- End Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Tanggal Selesai
          </label>
          <input
            type="date"
            v-model="reportForm.end_date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>

        <!-- Format -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Format Export
          </label>
          <select
            v-model="reportForm.format"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="pdf">PDF</option>
            <option value="excel">Excel</option>
          </select>
        </div>
      </div>

      <!-- Additional Info -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Informasi Tambahan (Opsional)
        </label>
        <textarea
          v-model="reportForm.additional_info"
          rows="3"
          placeholder="Catatan tambahan untuk laporan..."
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        ></textarea>
      </div>

      <!-- Generate Button -->
      <button
        @click="generateReport"
        :disabled="loading"
        class="w-full md:w-auto px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center justify-center gap-2"
      >
        <svg v-if="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <svg v-else class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ loading ? 'Memproses...' : 'Generate & Download Laporan' }}
      </button>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Total Produksi</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total_production }}</p>
          </div>
          <div class="bg-blue-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Selesai</p>
            <p class="text-2xl font-bold text-green-600">{{ stats.completed }}</p>
          </div>
          <div class="bg-green-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Lulus QC</p>
            <p class="text-2xl font-bold text-blue-600">{{ stats.passed_qc }}</p>
          </div>
          <div class="bg-blue-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Reject</p>
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

    <!-- Production Plans Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <h2 class="text-xl font-semibold text-gray-900">Riwayat Produksi</h2>
          
          <div class="flex flex-col md:flex-row gap-3">
            <!-- Status Filter -->
            <select
              v-model="filters.status"
              @change="fetchProductionPlans"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Semua Status</option>
              <option value="completed">Selesai</option>
              <option value="in_progress">Dalam Proses</option>
              <option value="approved">Disetujui</option>
            </select>

            <!-- Search -->
            <div class="relative">
              <input
                type="text"
                v-model="filters.search"
                @input="debouncedSearch"
                placeholder="Cari produk..."
                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
              <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produksi</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QC Pass</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QC Reject</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="loading" v-for="n in 5" :key="n">
              <td colspan="9" class="px-6 py-4">
                <div class="animate-pulse flex space-x-4">
                  <div class="flex-1 space-y-2">
                    <div class="h-4 bg-gray-200 rounded"></div>
                  </div>
                </div>
              </td>
            </tr>
            
            <tr v-else-if="productionPlans.length === 0">
              <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-lg font-medium">Tidak ada data produksi</p>
                <p class="text-sm">Data akan muncul setelah produksi dibuat</p>
              </td>
            </tr>

            <tr v-else v-for="plan in productionPlans" :key="plan.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                #{{ plan.id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ plan.product?.name }}</div>
                <div class="text-sm text-gray-500">{{ plan.product?.sku }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatNumber(plan.quantity) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatNumber(plan.produced_quantity) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-medium text-green-600">
                  {{ formatNumber(plan.quality_control?.passed_quantity || 0) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-medium text-red-600">
                  {{ formatNumber(plan.quality_control?.failed_quantity || 0) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusClass(plan.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ getStatusLabel(plan.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(plan.target_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button
                  @click="viewDetail(plan)"
                  class="text-blue-600 hover:text-blue-900 mr-3"
                >
                  Detail
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > 0" class="px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Menampilkan {{ pagination.from }} - {{ pagination.to }} dari {{ pagination.total }} data
          </div>
          <div class="flex gap-2">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-3 py-1 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
            >
              Prev
            </button>
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="changePage(page)"
              :class="[
                'px-3 py-1 border rounded-lg',
                page === pagination.current_page
                  ? 'bg-blue-600 text-white border-blue-600'
                  : 'border-gray-300 hover:bg-gray-50'
              ]"
            >
              {{ page }}
            </button>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <div v-if="showDetailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="text-2xl font-bold text-gray-900">Detail Order Produksi #{{ selectedPlan.id }}</h3>
            <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="p-6 space-y-6">
          <!-- Product Info -->
          <div class="bg-blue-50 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-3">Informasi Produk</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-600">Nama Produk</p>
                <p class="font-medium">{{ selectedPlan.product?.name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">SKU</p>
                <p class="font-medium">{{ selectedPlan.product?.sku }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Harga</p>
                <p class="font-medium">Rp {{ formatNumber(selectedPlan.product?.price) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Deskripsi</p>
                <p class="font-medium">{{ selectedPlan.product?.description }}</p>
              </div>
            </div>
          </div>

          <!-- Production Info -->
          <div class="bg-green-50 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-3">Informasi Produksi</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-600">Target Produksi</p>
                <p class="font-medium">{{ formatNumber(selectedPlan.quantity) }} unit</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Jumlah Diproduksi</p>
                <p class="font-medium">{{ formatNumber(selectedPlan.produced_quantity) }} unit</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Target Date</p>
                <p class="font-medium">{{ formatDate(selectedPlan.target_date) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Selesai Pada</p>
                <p class="font-medium">{{ formatDateTime(selectedPlan.completed_at) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Prioritas</p>
                <span :class="getPriorityClass(selectedPlan.priority)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ getPriorityLabel(selectedPlan.priority) }}
                </span>
              </div>
              <div>
                <p class="text-sm text-gray-600">Progress</p>
                <p class="font-medium">{{ selectedPlan.progress_percentage }}%</p>
              </div>
            </div>
          </div>

          <!-- Quality Control Info -->
          <div v-if="selectedPlan.quality_control" class="bg-yellow-50 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-3">Hasil Quality Control</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-600">Total Inspeksi</p>
                <p class="font-medium">{{ formatNumber(selectedPlan.quality_control.total_quantity) }} unit</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Lulus QC</p>
                <p class="font-medium text-green-600">{{ formatNumber(selectedPlan.quality_control.passed_quantity) }} unit</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Reject</p>
                <p class="font-medium text-red-600">{{ formatNumber(selectedPlan.quality_control.failed_quantity) }} unit</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Pass Rate</p>
                <p class="font-medium">{{ calculatePassRate(selectedPlan.quality_control) }}%</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Inspector</p>
                <p class="font-medium">{{ selectedPlan.quality_control.inspector?.name || '-' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Tanggal Inspeksi</p>
                <p class="font-medium">{{ formatDateTime(selectedPlan.quality_control.inspection_date) }}</p>
              </div>
            </div>
            <div v-if="selectedPlan.quality_control.notes" class="mt-4">
              <p class="text-sm text-gray-600">Catatan QC</p>
              <p class="font-medium mt-1">{{ selectedPlan.quality_control.notes }}</p>
            </div>
          </div>

          <!-- Timeline -->
          <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-3">Timeline</h4>
            <div class="space-y-3">
              <div class="flex items-start gap-3">
                <div class="w-2 h-2 bg-blue-600 rounded-full mt-2"></div>
                <div>
                  <p class="font-medium">Dibuat</p>
                  <p class="text-sm text-gray-600">{{ formatDateTime(selectedPlan.created_at) }} oleh {{ selectedPlan.created_by?.name }}</p>
                </div>
              </div>
              <div v-if="selectedPlan.submitted_at" class="flex items-start gap-3">
                <div class="w-2 h-2 bg-yellow-600 rounded-full mt-2"></div>
                <div>
                  <p class="font-medium">Diajukan</p>
                  <p class="text-sm text-gray-600">{{ formatDateTime(selectedPlan.submitted_at) }}</p>
                </div>
              </div>
              <div v-if="selectedPlan.approved_at" class="flex items-start gap-3">
                <div class="w-2 h-2 bg-green-600 rounded-full mt-2"></div>
                <div>
                  <p class="font-medium">Disetujui</p>
                  <p class="text-sm text-gray-600">{{ formatDateTime(selectedPlan.approved_at) }} oleh {{ selectedPlan.approved_by?.name }}</p>
                </div>
              </div>
              <div v-if="selectedPlan.production_started_at" class="flex items-start gap-3">
                <div class="w-2 h-2 bg-purple-600 rounded-full mt-2"></div>
                <div>
                  <p class="font-medium">Produksi Dimulai</p>
                  <p class="text-sm text-gray-600">{{ formatDateTime(selectedPlan.production_started_at) }}</p>
                </div>
              </div>
              <div v-if="selectedPlan.completed_at" class="flex items-start gap-3">
                <div class="w-2 h-2 bg-green-700 rounded-full mt-2"></div>
                <div>
                  <p class="font-medium">Selesai</p>
                  <p class="text-sm text-gray-600">{{ formatDateTime(selectedPlan.completed_at) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="selectedPlan.notes || selectedPlan.progress_notes" class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-semibold text-gray-900 mb-3">Catatan</h4>
            <div v-if="selectedPlan.notes" class="mb-3">
              <p class="text-sm text-gray-600">Catatan Plan</p>
              <p class="mt-1">{{ selectedPlan.notes }}</p>
            </div>
            <div v-if="selectedPlan.progress_notes">
              <p class="text-sm text-gray-600">Progress Notes</p>
              <pre class="mt-1 text-sm whitespace-pre-wrap">{{ selectedPlan.progress_notes }}</pre>
            </div>
          </div>
        </div>

        <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
          <button
            @click="exportSingleReport"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
          >
            Export PDF
          </button>
          <button
            @click="showDetailModal = false"
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300"
          >
            Tutup
          </button>
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
import { ref, reactive, computed, onMounted } from 'vue';

export default {
  name: 'ProductionReports',
  setup() {
    const API_URL = '/api/produksi'; // Adjust based on your API
    const loading = ref(false);
    const productionPlans = ref([]);
    const selectedPlan = ref(null);
    const showDetailModal = ref(false);
    
    const stats = reactive({
      total_production: 0,
      completed: 0,
      passed_qc: 0,
      rejected: 0
    });

    const reportForm = reactive({
      report_type: 'weekly',
      start_date: '',
      end_date: '',
      format: 'pdf',
      additional_info: ''
    });

    const filters = reactive({
      status: '',
      search: '',
      start_date: '',
      end_date: ''
    });

    const pagination = reactive({
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: 0,
      to: 0
    });

    const toast = reactive({
      show: false,
      message: '',
      type: 'success'
    });

    // Computed
    const visiblePages = computed(() => {
      const pages = [];
      const maxVisible = 5;
      let start = Math.max(1, pagination.current_page - Math.floor(maxVisible / 2));
      let end = Math.min(pagination.last_page, start + maxVisible - 1);
      
      if (end - start + 1 < maxVisible) {
        start = Math.max(1, end - maxVisible + 1);
      }
      
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      return pages;
    });

    // Methods
    const showToast = (message, type = 'success') => {
      toast.message = message;
      toast.type = type;
      toast.show = true;
      setTimeout(() => {
        toast.show = false;
      }, 3000);
    };

    const fetchProductionPlans = async (page = 1) => {
      loading.value = true;
      try {
        const params = new URLSearchParams({
          page,
          per_page: pagination.per_page
        });

        if (filters.status) params.append('status', filters.status);
        if (filters.start_date) params.append('start_date', filters.start_date);
        if (filters.end_date) params.append('end_date', filters.end_date);

        const response = await fetch(`${API_URL}/production-plans?${params}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        });

        if (!response.ok) throw new Error('Failed to fetch data');

        const data = await response.json();
        
        // Fetch all QC data
        const qcResponse = await fetch(`${API_URL}/quality-control?per_page=1000`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        });
        
        let qcMap = {};
        if (qcResponse.ok) {
          const qcData = await qcResponse.json();
          // Create map of production_plan_id to QC data
          if (qcData.data) {
            qcData.data.forEach(qc => {
              qcMap[qc.production_plan_id] = qc;
            });
          }
        }
        
        // Attach QC data to each production plan
        const plansWithQC = data.data.map(plan => {
          if (qcMap[plan.id]) {
            plan.quality_control = qcMap[plan.id];
          }
          return plan;
        });
        
        productionPlans.value = plansWithQC;
        pagination.current_page = data.current_page;
        pagination.last_page = data.last_page;
        pagination.total = data.total;
        pagination.from = data.from;
        pagination.to = data.to;

        // Calculate stats
        calculateStats();
      } catch (error) {
        console.error('Error fetching production plans:', error);
        showToast('Gagal memuat data produksi', 'error');
      } finally {
        loading.value = false;
      }
    };

    const calculateStats = () => {
      stats.total_production = productionPlans.value.length;
      stats.completed = productionPlans.value.filter(p => p.status === 'completed').length;
      stats.passed_qc = productionPlans.value.reduce((sum, p) => sum + (p.quality_control?.passed_quantity || 0), 0);
      stats.rejected = productionPlans.value.reduce((sum, p) => sum + (p.quality_control?.failed_quantity || 0), 0);
    };

    const generateReport = async () => {
      if (!reportForm.start_date || !reportForm.end_date) {
        showToast('Tanggal mulai dan selesai harus diisi', 'error');
        return;
      }

      if (new Date(reportForm.start_date) > new Date(reportForm.end_date)) {
        showToast('Tanggal mulai tidak boleh lebih dari tanggal selesai', 'error');
        return;
      }

      loading.value = true;
      try {
        const response = await fetch(`${API_URL}/production-report/export`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(reportForm)
        });

        if (!response.ok) {
          const error = await response.json();
          throw new Error(error.message || 'Failed to generate report');
        }

        // Download file
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Laporan-Produksi-${reportForm.start_date}-${reportForm.end_date}.${reportForm.format === 'pdf' ? 'pdf' : 'xlsx'}`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        showToast('Laporan berhasil diunduh', 'success');
      } catch (error) {
        console.error('Error generating report:', error);
        showToast(error.message || 'Gagal generate laporan', 'error');
      } finally {
        loading.value = false;
      }
    };

    const viewDetail = async (plan) => {
      loading.value = true;
      try {
        const response = await fetch(`${API_URL}/production-plans/${plan.id}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        });

        if (!response.ok) throw new Error('Failed to fetch detail');

        const data = await response.json();
        selectedPlan.value = data;
        
        // Fetch QC data if available
        if (data.status === 'completed') {
          await fetchQualityControl(data.id);
        }
        
        // Fetch logistics data
        await fetchLogistics(data.product_id);
        
        showDetailModal.value = true;
      } catch (error) {
        console.error('Error fetching detail:', error);
        showToast('Gagal memuat detail', 'error');
      } finally {
        loading.value = false;
      }
    };

    const fetchQualityControl = async (planId) => {
      try {
        // Fetch all QC records to find matching production_plan_id
        const response = await fetch(`${API_URL}/quality-control?per_page=1000`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        });

        if (response.ok) {
          const data = await response.json();
          // Find QC record that matches the production plan ID
          if (data.data) {
            const qcRecord = data.data.find(qc => qc.production_plan_id === planId);
            
            if (qcRecord && selectedPlan.value) {
              selectedPlan.value.quality_control = qcRecord;
            }
          }
        }
      } catch (error) {
        console.error('Error fetching QC:', error);
      }
    };

    const fetchLogistics = async (productId) => {
      try {
        const response = await fetch(`${API_URL}/logistics?product_id=${productId}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
          }
        });

        if (response.ok) {
          const data = await response.json();
          if (data.success && data.data) {
            selectedPlan.value.logistics = data.data;
          }
        }
      } catch (error) {
        console.error('Error fetching logistics:', error);
      }
    };

    const exportSingleReport = async () => {
      if (!selectedPlan.value) return;

      loading.value = true;
      try {
        const response = await fetch(`${API_URL}/production-report/export`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            report_type: 'single',
            production_plan_id: selectedPlan.value.id,
            start_date: selectedPlan.value.target_date,
            end_date: selectedPlan.value.target_date,
            format: 'pdf'
          })
        });

        if (!response.ok) throw new Error('Failed to export report');

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Laporan-Order-${selectedPlan.value.id}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        showToast('Laporan berhasil diunduh', 'success');
      } catch (error) {
        console.error('Error exporting report:', error);
        showToast('Gagal export laporan', 'error');
      } finally {
        loading.value = false;
      }
    };

    const changePage = (page) => {
      if (page < 1 || page > pagination.last_page) return;
      fetchProductionPlans(page);
    };

    let searchTimeout;
    const debouncedSearch = () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        fetchProductionPlans(1);
      }, 500);
    };

    // Helper functions
    const formatNumber = (num) => {
      return new Intl.NumberFormat('id-ID').format(num || 0);
    };

    const formatDate = (date) => {
      if (!date) return '-';
      return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    };

    const formatDateTime = (date) => {
      if (!date) return '-';
      return new Date(date).toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    };

    const getStatusClass = (status) => {
      const classes = {
        'draft': 'bg-gray-100 text-gray-800',
        'pending_approval': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-blue-100 text-blue-800',
        'rejected': 'bg-red-100 text-red-800',
        'in_progress': 'bg-purple-100 text-purple-800',
        'completed': 'bg-green-100 text-green-800'
      };
      return classes[status] || 'bg-gray-100 text-gray-800';
    };

    const getStatusLabel = (status) => {
      const labels = {
        'draft': 'Draft',
        'pending_approval': 'Menunggu Persetujuan',
        'approved': 'Disetujui',
        'rejected': 'Ditolak',
        'in_progress': 'Dalam Proses',
        'completed': 'Selesai'
      };
      return labels[status] || status;
    };

    const getPriorityClass = (priority) => {
      const classes = {
        'low': 'bg-gray-100 text-gray-800',
        'medium': 'bg-blue-100 text-blue-800',
        'high': 'bg-orange-100 text-orange-800',
        'urgent': 'bg-red-100 text-red-800'
      };
      return classes[priority] || 'bg-gray-100 text-gray-800';
    };

    const getPriorityLabel = (priority) => {
      const labels = {
        'low': 'Rendah',
        'medium': 'Sedang',
        'high': 'Tinggi',
        'urgent': 'Mendesak'
      };
      return labels[priority] || priority;
    };

    const calculatePassRate = (qc) => {
      if (!qc || qc.total_quantity === 0) return 0;
      return ((qc.passed_quantity / qc.total_quantity) * 100).toFixed(2);
    };

    // Initialize dates
    const initializeDates = () => {
      const today = new Date();
      const lastWeek = new Date(today);
      lastWeek.setDate(today.getDate() - 7);
      
      reportForm.end_date = today.toISOString().split('T')[0];
      reportForm.start_date = lastWeek.toISOString().split('T')[0];
    };

    onMounted(() => {
      initializeDates();
      fetchProductionPlans();
    });

    return {
      loading,
      productionPlans,
      selectedPlan,
      showDetailModal,
      stats,
      reportForm,
      filters,
      pagination,
      toast,
      visiblePages,
      fetchProductionPlans,
      generateReport,
      viewDetail,
      exportSingleReport,
      changePage,
      debouncedSearch,
      formatNumber,
      formatDate,
      formatDateTime,
      getStatusClass,
      getStatusLabel,
      getPriorityClass,
      getPriorityLabel,
      calculatePassRate
    };
  }
};
</script>