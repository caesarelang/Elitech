<template>
  <div class="ppic-staff-container">
    <!-- Header -->
    <div class="page-header">
      <h1>Manajemen Rencana Produksi</h1>
      <button @click="showCreateModal = true" class="btn btn-primary">
        <i class="fas fa-plus"></i> Buat Rencana Baru
      </button>
    </div>

    <!-- Dashboard Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon draft">
          <i class="fas fa-file-alt"></i>
        </div>
        <div class="stat-info">
          <h3>{{ stats.draft || 0 }}</h3>
          <p>Draft</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon pending">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
          <h3>{{ stats.pending_approval || 0 }}</h3>
          <p>Menunggu Approval</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon progress">
          <i class="fas fa-cog"></i>
        </div>
        <div class="stat-info">
          <h3>{{ stats.in_progress || 0 }}</h3>
          <p>Sedang Diproses</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon completed">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
          <h3>{{ stats.completed_today || 0 }}</h3>
          <p>Selesai Hari Ini</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <div class="filter-group">
        <label>Status:</label>
        <select v-model="filters.status" @change="loadPlans">
          <option value="">Semua Status</option>
          <option value="draft">Draft</option>
          <option value="pending_approval">Menunggu Approval</option>
          <option value="approved">Disetujui</option>
          <option value="rejected">Ditolak</option>
          <option value="in_progress">Sedang Diproses</option>
          <option value="completed">Selesai</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Dari Tanggal:</label>
        <input type="date" v-model="filters.start_date" @change="loadPlans" />
      </div>
      <div class="filter-group">
        <label>Sampai Tanggal:</label>
        <input type="date" v-model="filters.end_date" @change="loadPlans" />
      </div>
      <button @click="resetFilters" class="btn btn-secondary">
        <i class="fas fa-redo"></i> Reset
      </button>
    </div>

    <!-- Production Plans Table -->
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Produk</th>
            <th>Jumlah</th>
            <th>Target</th>
            <th>Prioritas</th>
            <th>Status</th>
            <th>Deadline</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="plan in plans" :key="plan.id">
            <td>#{{ plan.id }}</td>
            <td>{{ plan.product?.name || '-' }}</td>
            <td>{{ plan.quantity }}</td>
            <td>{{ formatDate(plan.target_date) }}</td>
            <td>
              <span :class="['badge', 'priority-' + plan.priority]">
                {{ plan.priority_label }}
              </span>
            </td>
            <td>
              <span :class="['badge', 'status-' + plan.status]">
                {{ plan.status_label }}
              </span>
            </td>
            <td>
              <span v-if="plan.deadline" :class="{ 'text-danger': plan.is_overdue }">
                {{ formatDate(plan.deadline) }}
                <i v-if="plan.is_overdue" class="fas fa-exclamation-triangle"></i>
              </span>
              <span v-else>-</span>
            </td>
            <td>
              <div class="action-buttons">
                <button @click="viewDetail(plan)" class="btn-icon btn-info" title="Detail">
                  <i class="fas fa-eye"></i>
                </button>
                <button v-if="plan.status === 'draft'" @click="editPlan(plan)" class="btn-icon btn-warning" title="Edit">
                  <i class="fas fa-edit"></i>
                </button>
                <button v-if="plan.status === 'draft'" @click="submitForApproval(plan)" class="btn-icon btn-success" title="Ajukan">
                  <i class="fas fa-paper-plane"></i>
                </button>
                <button v-if="plan.status === 'draft'" @click="deletePlan(plan)" class="btn-icon btn-danger" title="Hapus">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="loading" class="loading">Memuat data...</div>
      <div v-if="!loading && plans.length === 0" class="no-data">Tidak ada data</div>
    </div>

    <!-- Pagination -->
    <div class="pagination" v-if="pagination.last_page > 1">
      <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="btn btn-secondary">
        <i class="fas fa-chevron-left"></i>
      </button>
      <span>Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}</span>
      <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="btn btn-secondary">
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="closeCreateModal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>{{ editMode ? 'Edit Rencana Produksi' : 'Buat Rencana Produksi Baru' }}</h2>
          <button @click="closeCreateModal" class="btn-close">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="savePlan">
            <div class="form-group">
              <label>Produk *</label>
              <select v-model="form.product_id" required>
                <option value="">Pilih Produk</option>
                <option v-for="product in products" :key="product.id" :value="product.id">
                  {{ product.name }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Jumlah *</label>
              <input type="number" v-model="form.quantity" min="1" required />
            </div>
            <div class="form-group">
              <label>Tanggal Target *</label>
              <input type="date" v-model="form.target_date" required />
            </div>
            <div class="form-group">
              <label>Prioritas *</label>
              <select v-model="form.priority" required>
                <option value="low">Rendah</option>
                <option value="medium">Sedang</option>
                <option value="high">Tinggi</option>
                <option value="urgent">Mendesak</option>
              </select>
            </div>
            <div class="form-group">
              <label>Catatan</label>
              <textarea v-model="form.notes" rows="4"></textarea>
            </div>
            <div class="form-actions">
              <button type="button" @click="closeCreateModal" class="btn btn-secondary">Batal</button>
              <button type="submit" class="btn btn-primary" :disabled="saving">
                {{ saving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Progress Modal -->
    <div v-if="showProgressModal" class="modal-overlay" @click.self="showProgressModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Update Progress Produksi</h2>
          <button @click="showProgressModal = false" class="btn-close">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveProgress">
            <div class="form-group">
              <label>Progress (%) *</label>
              <input type="number" v-model="progressForm.progress_percentage" min="0" max="100" required />
            </div>
            <div class="form-group">
              <label>Jumlah Diproduksi *</label>
              <input type="number" v-model="progressForm.produced_quantity" min="0" required />
            </div>
            <div class="form-group">
              <label>Catatan Progress</label>
              <textarea v-model="progressForm.progress_notes" rows="4"></textarea>
            </div>
            <div class="form-actions">
              <button type="button" @click="showProgressModal = false" class="btn btn-secondary">Batal</button>
              <button type="submit" class="btn btn-primary" :disabled="saving">
                {{ saving ? 'Menyimpan...' : 'Update Progress' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <div v-if="showDetailModal" class="modal-overlay" @click.self="showDetailModal = false">
      <div class="modal-content modal-large">
        <div class="modal-header">
          <h2>Detail Rencana Produksi #{{ selectedPlan?.id }}</h2>
          <button @click="showDetailModal = false" class="btn-close">&times;</button>
        </div>
        <div class="modal-body">
          <div class="detail-grid">
            <div class="detail-item">
              <label>Produk:</label>
              <span>{{ selectedPlan?.product?.name }}</span>
            </div>
            <div class="detail-item">
              <label>Jumlah:</label>
              <span>{{ selectedPlan?.quantity }}</span>
            </div>
            <div class="detail-item">
              <label>Target Tanggal:</label>
              <span>{{ formatDate(selectedPlan?.target_date) }}</span>
            </div>
            <div class="detail-item">
              <label>Prioritas:</label>
              <span :class="['badge', 'priority-' + selectedPlan?.priority]">
                {{ selectedPlan?.priority_label }}
              </span>
            </div>
            <div class="detail-item">
              <label>Status:</label>
              <span :class="['badge', 'status-' + selectedPlan?.status]">
                {{ selectedPlan?.status_label }}
              </span>
            </div>
            <div class="detail-item">
              <label>Dibuat Oleh:</label>
              <span>{{ selectedPlan?.created_by?.name }}</span>
            </div>
            <div class="detail-item" v-if="selectedPlan?.deadline">
              <label>Deadline:</label>
              <span>{{ formatDate(selectedPlan?.deadline) }}</span>
            </div>
            <div class="detail-item" v-if="selectedPlan?.approved_by">
              <label>Disetujui Oleh:</label>
              <span>{{ selectedPlan?.approved_by?.name }}</span>
            </div>
            <div class="detail-item full-width" v-if="selectedPlan?.notes">
              <label>Catatan:</label>
              <span>{{ selectedPlan?.notes }}</span>
            </div>
            <div class="detail-item full-width" v-if="selectedPlan?.approval_notes">
              <label>Catatan Approval:</label>
              <span>{{ selectedPlan?.approval_notes }}</span>
            </div>
            <div class="detail-item full-width" v-if="selectedPlan?.progress_notes">
              <label>Catatan Progress:</label>
              <span>{{ selectedPlan?.progress_notes }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Generate Report Section -->
    <div class="report-section">
      <h2>Generate Laporan Produksi</h2>

<form @submit.prevent="generateReport" class="report-form">

  <div class="form-group">
    <label>Tanggal Mulai:</label>
    <input type="date" v-model="reportForm.start_date" required />
  </div>

  <div class="form-group">
    <label>Tanggal Akhir:</label>
    <input type="date" v-model="reportForm.end_date" required />
  </div>

  <!-- 🟢 Tambahkan di sini -->
  <div class="form-group">
    <label>Format File:</label>
    <select v-model="reportForm.format" required>
      <option value="pdf">PDF</option>
      <option value="excel">Excel</option>
    </select>
  </div>
  <!-- 🟢 Sampai sini -->

  <button type="submit" class="btn btn-success" :disabled="generatingReport">
    <i class="fas fa-file-alt"></i>
    {{ generatingReport ? 'Membuat Laporan...' : 'Generate Laporan' }}
  </button>
</form>


    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'StaffPPIC',
  data() {
    return {
      plans: [],
      products: [],
      stats: {},
      loading: false,
      saving: false,
      generatingReport: false,
      showCreateModal: false,
      showProgressModal: false,
      showDetailModal: false,
      editMode: false,
      selectedPlan: null,
      filters: {
        status: '',
        start_date: '',
        end_date: ''
      },
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 15
      },
      form: {
        product_id: '',
        quantity: '',
        target_date: '',
        priority: 'medium',
        notes: ''
      },
      progressForm: {
        progress_percentage: 0,
        produced_quantity: 0,
        progress_notes: ''
      },
      reportForm: {
        report_type: 'weekly',
        start_date: '',
        end_date: '',
        additional_info: '',
        format: 'pdf'
      }
    };
  },
  mounted() {
    this.loadStats();
    this.loadPlans();
    this.loadProducts();
  },
  methods: {
    async loadStats() {
      try {
        const response = await axios.get('/ppic/dashboard-stats');
        this.stats = response.data;
      } catch (error) {
        console.error('Error loading stats:', error);
      }
    },
    async loadPlans() {
      this.loading = true;
      try {
        const params = {
          page: this.pagination.current_page,
          per_page: this.pagination.per_page,
          ...this.filters
        };
        const response = await axios.get('/production-plans', { params });
        this.plans = response.data.data;
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page
        };
      } catch (error) {
        console.error('Error loading plans:', error);
        this.$toast.error('Gagal memuat data rencana produksi');
      } finally {
        this.loading = false;
      }
    },
    async loadProducts() {
      try {
        const response = await axios.get('/products');
        this.products = response.data.data || response.data;
      } catch (error) {
        console.error('Error loading products:', error);
      }
    },
async savePlan() {
      // Validasi form
      if (!this.form.product_id) {
        alert('Pilih produk terlebih dahulu');
        return;
      }
      if (!this.form.quantity || this.form.quantity < 1) {
        alert('Jumlah harus lebih dari 0');
        return;
      }
      if (!this.form.target_date) {
        alert('Pilih tanggal target');
        return;
      }

      this.saving = true;
      try {
        let response;
        if (this.editMode) {
          response = await axios.put(`/production-plans/${this.selectedPlan.id}`, this.form);
          alert('Rencana produksi berhasil diupdate');
        } else {
          response = await axios.post('/production-plans', this.form);
          alert('Rencana produksi berhasil dibuat');
        }
        
        console.log('Save success:', response.data);
        
        // Tutup modal dulu
        this.closeCreateModal();
        
        // Reload data
        await this.loadPlans();
        await this.loadStats();
        
      } catch (error) {
        console.error('Error saving plan:', error);
        console.error('Error response:', error.response);
        
        // Tampilkan error lebih detail
        let errorMessage = 'Gagal menyimpan rencana produksi';
        
        if (error.response?.data?.errors) {
          const errors = error.response.data.errors;
          const firstError = Object.values(errors)[0][0];
          errorMessage = firstError;
        } else if (error.response?.data?.message) {
          errorMessage = error.response.data.message;
        }
        
        alert(errorMessage);
      } finally {
        this.saving = false;
      }
    },
    async submitForApproval(plan) {
      if (!confirm('Ajukan rencana produksi ini untuk persetujuan?')) return;
      try {
        await axios.post(`/production-plans/${plan.id}/submit`);
        this.$toast.success('Rencana produksi berhasil diajukan');
        this.loadPlans();
        this.loadStats();
      } catch (error) {
        console.error('Error submitting plan:', error);
        this.$toast.error('Gagal mengajukan rencana produksi');
      }
    },
    async startProduction(plan) {
      if (!confirm('Mulai produksi untuk rencana ini?')) return;
      try {
        await axios.post(`/production-plans/${plan.id}/start`);
        this.$toast.success('Produksi dimulai');
        this.loadPlans();
        this.loadStats();
      } catch (error) {
        console.error('Error starting production:', error);
        this.$toast.error('Gagal memulai produksi');
      }
    },
    updateProgress(plan) {
      this.selectedPlan = plan;
      this.progressForm.progress_percentage = plan.progress_percentage || 0;
      this.progressForm.produced_quantity = plan.produced_quantity || 0;
      this.progressForm.progress_notes = plan.progress_notes || '';
      this.showProgressModal = true;
    },
    async saveProgress() {
      this.saving = true;
      try {
        await axios.put(`/production-plans/${this.selectedPlan.id}/progress`, this.progressForm);
        this.$toast.success('Progress berhasil diupdate');
        this.showProgressModal = false;
        this.loadPlans();
        this.loadStats();
      } catch (error) {
        console.error('Error updating progress:', error);
        this.$toast.error('Gagal update progress');
      } finally {
        this.saving = false;
      }
    },
    async deletePlan(plan) {
      if (!confirm('Hapus rencana produksi ini?')) return;
      try {
        await axios.delete(`/production-plans/${plan.id}`);
        this.$toast.success('Rencana produksi berhasil dihapus');
        this.loadPlans();
        this.loadStats();
      } catch (error) {
        console.error('Error deleting plan:', error);
        this.$toast.error('Gagal menghapus rencana produksi');
      }
    },
    editPlan(plan) {
      this.editMode = true;
      this.selectedPlan = plan;
      this.form = {
        product_id: plan.product_id,
        quantity: plan.quantity,
        target_date: plan.target_date,
        priority: plan.priority,
        notes: plan.notes || ''
      };
      this.showCreateModal = true;
    },
    viewDetail(plan) {
      this.selectedPlan = plan;
      this.showDetailModal = true;
    },
    closeCreateModal() {
      this.showCreateModal = false;
      this.editMode = false;
      this.selectedPlan = null;
      this.form = {
        product_id: '',
        quantity: '',
        target_date: '',
        priority: 'medium',
        notes: ''
      };
    },
async generateReport() {
  this.generatingReport = true;
  try {
    const response = await axios.post(
      '/ppic/production-report/export',
      this.reportForm,
      { responseType: 'blob' } 
    );

    const blob = new Blob([response.data], {
      type:
        this.reportForm.format === 'pdf'
          ? 'application/pdf'
          : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    });

    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download =
      this.reportForm.format === 'pdf'
        ? 'Laporan-Produksi.pdf'
        : 'Laporan-Produksi.xlsx';
    a.click();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error(error);
    alert('Gagal generate laporan');
  } finally {
    this.generatingReport = false;
  }
}

,
    resetFilters() {
      this.filters = {
        status: '',
        start_date: '',
        end_date: ''
      };
      this.loadPlans();
    },
    changePage(page) {
      this.pagination.current_page = page;
      this.loadPlans();
    },
    formatDate(date) {
      if (!date) return '-';
      return new Date(date).toLocaleDateString('id-ID');
    }
  }
};
</script>

<style scoped>
.ppic-staff-container {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  display: flex;
  align-items: center;
  gap: 15px;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
}

.stat-icon.draft { background: #6c757d; }
.stat-icon.pending { background: #ffc107; }
.stat-icon.progress { background: #17a2b8; }
.stat-icon.completed { background: #28a745; }

.stat-info h3 {
  margin: 0;
  font-size: 32px;
  font-weight: bold;
}

.stat-info p {
  margin: 5px 0 0;
  color: #666;
}

.filters-section {
  background: white;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  display: flex;
  gap: 15px;
  align-items: end;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.filter-group label {
  font-weight: 500;
  font-size: 14px;
}

.filter-group select,
.filter-group input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  min-width: 150px;
}

.table-container {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.data-table th {
  background: #f8f9fa;
  font-weight: 600;
}

.badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.priority-low { background: #d1ecf1; color: #0c5460; }
.priority-medium { background: #fff3cd; color: #856404; }
.priority-high { background: #f8d7da; color: #721c24; }
.priority-urgent { background: #dc3545; color: white; }

.status-draft { background: #e2e3e5; color: #383d41; }
.status-pending_approval { background: #fff3cd; color: #856404; }
.status-approved { background: #d4edda; color: #155724; }
.status-rejected { background: #f8d7da; color: #721c24; }
.status-in_progress { background: #d1ecf1; color: #0c5460; }
.status-completed { background: #d4edda; color: #155724; }

.progress-bar {
  position: relative;
  height: 20px;
  background: #e9ecef;
  border-radius: 10px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #28a745;
  transition: width 0.3s;
}

.progress-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 12px;
  font-weight: 600;
}

.action-buttons {
  display: flex;
  gap: 5px;
}

.btn-icon {
  padding: 15px 15px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  color: white;
  transition: opacity 0.2s;
}

.btn-icon:hover { opacity: 0.8; }
.btn-icon.btn-info { background: #17a2b8; }
.btn-icon.btn-warning { background: #ffc107; color: #333; }
.btn-icon.btn-success { background: #28a745; }
.btn-icon.btn-primary { background: #007bff; }
.btn-icon.btn-danger { background: #dc3545; }

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover { background: #0056b3; }

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover { background: #545b62; }

.btn-success {
  background: #28a745;
  color: white;
}

.btn-success:hover { background: #218838; }

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-large {
  max-width: 800px;
}

.modal-header {
  padding: 20px;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 28px;
  cursor: pointer;
  color: #999;
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-family: inherit;
}

.form-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 20px;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.detail-item.full-width {
  grid-column: 1 / -1;
}

.detail-item label {
  font-weight: 600;
  color: #666;
  font-size: 14px;
}

.detail-item span {
  font-size: 15px;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
  margin-top: 20px;
  padding: 20px;
  background: white;
  border-radius: 8px;
}

.loading,
.no-data {
  text-align: center;
  padding: 40px;
  color: #666;
}

.text-danger {
  color: #dc3545;
}

.report-section {
  background: white;
  padding: 20px;
  border-radius: 8px;
  margin-top: 30px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.report-section h2 {
  margin-bottom: 20px;
}

.report-form {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  align-items: end;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }
  
  .table-container {
    overflow-x: auto;
  }
  
  .detail-grid {
    grid-template-columns: 1fr;
  }
  
  .report-form {
    grid-template-columns: 1fr;
  }
}
</style>