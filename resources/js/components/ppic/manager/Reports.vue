<template>
  <div class="p-6 min-h-screen bg-gray-50">
    <h1 class="text-2xl font-bold mb-4">Laporan Produksi</h1>
    <p class="mb-6 text-gray-600">Berikut adalah seluruh data rencana produksi yang tercatat.</p>

    <div v-if="loading" class="text-center text-gray-500 py-10">
      Memuat data laporan...
    </div>

    <div v-else>
      <div class="overflow-x-auto bg-white rounded-lg shadow-md">
        <table class="min-w-full border border-gray-300">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-4 py-2 border">ID</th>
              <th class="px-4 py-2 border">Produk</th>
              <th class="px-4 py-2 border">Jumlah</th>
              <th class="px-4 py-2 border">Prioritas</th>
              <th class="px-4 py-2 border">Status</th>
              <th class="px-4 py-2 border">Tanggal Target</th>
              <th class="px-4 py-2 border">Deadline</th>
              <th class="px-4 py-2 border">Mulai Produksi</th>
              <th class="px-4 py-2 border">Selesai Produksi</th>
              <th class="px-4 py-2 border">Progress (%)</th>
              <th class="px-4 py-2 border">Jumlah Selesai</th>
              <th class="px-4 py-2 border">Dibuat Oleh</th>
              <th class="px-4 py-2 border">Disetujui Oleh</th>
              <th class="px-4 py-2 border">Catatan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="plan in productionPlans" :key="plan.id" class="hover:bg-gray-50">
              <td class="px-4 py-2 border">{{ plan.id }}</td>
              <td class="px-4 py-2 border">{{ plan.product?.name || '-' }}</td>
              <td class="px-4 py-2 border">{{ plan.quantity }}</td>
              <td class="px-4 py-2 border">{{ plan.priority_label }}</td>
              <td class="px-4 py-2 border">{{ plan.status_label }}</td>
              <td class="px-4 py-2 border">{{ formatDate(plan.target_date) }}</td>
              <td class="px-4 py-2 border">{{ formatDate(plan.deadline) }}</td>
              <td class="px-4 py-2 border">{{ formatDate(plan.production_started_at) }}</td>
              <td class="px-4 py-2 border">{{ formatDate(plan.completed_at) }}</td>
              <td class="px-4 py-2 border text-center">{{ plan.progress_percentage ?? 0 }}</td>
              <td class="px-4 py-2 border text-center">{{ plan.produced_quantity ?? 0 }}</td>
              <td class="px-4 py-2 border">{{ plan.created_by_name || '-' }}</td>
              <td class="px-4 py-2 border">{{ plan.approval_notes || '-' }}</td>
              <td class="px-4 py-2 border">{{ plan.notes || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'Reports',
  setup() {
    const productionPlans = ref([])
    const loading = ref(true)

    const fetchReport = async () => {
      try {
        const res = await axios.get('/production-plans', {
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
        })
        productionPlans.value = res.data.data || res.data
      } catch (error) {
        console.error('Gagal memuat laporan:', error.response?.data || error)
      } finally {
        loading.value = false
      }
    }

    const formatDate = (datetime) => {
      if (!datetime) return '-'
      const date = new Date(datetime)
      if (isNaN(date)) return datetime // fallback kalau bukan format ISO
      return date.toISOString().split('T')[0] // hasil: YYYY-MM-DD
    }

    onMounted(fetchReport)

    return { productionPlans, loading, formatDate }
  },
}
</script>
