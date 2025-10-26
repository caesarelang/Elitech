<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm"></nav>

    <!-- Main Content -->
    <div class="py-10">
      <header>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
          <h1 class="text-2xl font-bold text-gray-900">
            Welcome, {{ user.name }}
          </h1>
        </div>
      </header>

      <main>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Daftar Rencana Produksi</h2>

            <!-- Loading state -->
            <div v-if="loading" class="text-center text-gray-500 py-10">
              Memuat data...
            </div>

            <!-- Table -->
            <table v-else class="min-w-full border border-gray-300">
              <thead class="bg-gray-200">
                <tr>
                  <th class="px-4 py-2 border">ID</th>
                  <th class="px-4 py-2 border">Produk</th>
                  <th class="px-4 py-2 border">Jumlah</th>
                  <th class="px-4 py-2 border">Tanggal Target</th>
                  <th class="px-4 py-2 border">Prioritas</th>
                  <th class="px-4 py-2 border">Status</th>
                  <th class="px-4 py-2 border text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="plan in productionPlans" :key="plan.id">
                  <td class="px-4 py-2 border">{{ plan.id }}</td>
                  <td class="px-4 py-2 border">{{ plan.product?.name || '-' }}</td>
                  <td class="px-4 py-2 border">{{ plan.quantity }}</td>

                  <!-- tampilkan hanya tanggal -->
                  <td class="px-4 py-2 border">
                    {{ formatDate(plan.target_date) }}
                  </td>

                  <td class="px-4 py-2 border capitalize">{{ plan.priority }}</td>
                  <td class="px-4 py-2 border capitalize">{{ plan.status }}</td>

                  <!-- dropdown aksi -->
                  <td class="px-4 py-2 border text-center">
                    <select
                      v-model="plan.status"
                      @change="updateStatus(plan)"
                      class="border border-gray-300 rounded-md px-2 py-1"
                      :disabled="!['pending_approval', 'approved', 'rejected'].includes(plan.status)"
                    >
                      <option value="pending_approval">Pending</option>
                      <option value="approved">Approved</option>
                      <option value="rejected">Rejected</option>
                    </select>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

export default {
  name: 'ManagerDashboard',
  setup() {
    const router = useRouter()
    const user = ref(JSON.parse(localStorage.getItem('user') || '{}'))
    const productionPlans = ref([])
    const loading = ref(true)

    const fetchPlans = async () => {
      try {
        const res = await axios.get('/production-plans', {
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
        })
        const allPlans = res.data.data || res.data
        productionPlans.value = allPlans.filter(plan => plan.status !== 'draft')
      } catch (error) {
        console.error('Gagal memuat data:', error.response?.data || error)
      } finally {
        loading.value = false
      }
    }

    const updateStatus = async (plan) => {
      try {
        const payload = { status: plan.status }
        if (plan.status === 'approved') {
          payload.approved_by = user.value.id
          payload.approved_at = new Date().toISOString()
          payload.approval_notes = 'Disetujui oleh manager PPIC'
        }

        await axios.put(`/production-plans/${plan.id}`, payload, {
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
        })

        alert(`Status plan #${plan.id} berhasil diubah ke ${plan.status}`)
      } catch (error) {
        console.error('Gagal ubah status:', error.response?.data || error)
        alert('Gagal mengubah status!')
      }
    }

    const formatDate = (dateString) => {
      if (!dateString) return '-'
      const date = new Date(dateString)
      return date.toISOString().split('T')[0] // hasil: YYYY-MM-DD
    }

    const logout = async () => {
      try {
        await axios.post('/logout', {}, {
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
      }
    })

    return {
      user,
      logout,
      productionPlans,
      updateStatus,
      loading,
      formatDate,
    }
  },
}
</script>
