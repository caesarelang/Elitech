<template>
  <div class="quality-control-page">
    <div class="page-header">
      <div>
        <h1 class="page-title">Quality Control</h1>
        <p class="page-subtitle">Manajemen inspeksi kualitas produksi</p>
      </div>
      <button @click="showCreateModal = true" class="btn btn-primary">
        <i class="fas fa-plus"></i>
        Buat Inspeksi Baru
      </button>
    </div>
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon pending">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <p class="stat-label">Menunggu Inspeksi</p>
          <h3 class="stat-value">{{ statistics.pending || 0 }}</h3>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon progress">
          <i class="fas fa-search"></i>
        </div>
        <div class="stat-content">
          <p class="stat-label">Sedang Diinspeksi</p>
          <h3 class="stat-value">{{ statistics.in_progress || 0 }}</h3>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon completed">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <p class="stat-label">Selesai</p>
          <h3 class="stat-value">{{ statistics.completed || 0 }}</h3>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon success">
          <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
          <p class="stat-label">Pass Rate</p>
          <h3 class="stat-value">{{ statistics.pass_rate || 0 }}%</h3>
        </div>
      </div>
    </div>
    <div class="filters-card">
      <div class="filters-grid">
        <div class="form-group">
          <label>Status</label>
          <select v-model="filters.status" @change="fetchQualityControls" class="form-control">
            <option value="">Semua Status</option>
            <option value="pending">Menunggu Inspeksi</option>
            <option value="in_progress">Sedang Diinspeksi</option>
            <option value="completed">Selesai</option>
          </select>
        </div>

        <div class="form-group">
          <label>Tanggal Mulai</label>
          <input 
            type="date" 
            v-model="filters.start_date" 
            @change="fetchQualityControls"
            class="form-control"
          />
        </div>

        <div class="form-group">
          <label>Tanggal Akhir</label>
          <input 
            type="date" 
            v-model="filters.end_date" 
            @change="fetchQualityControls"
            class="form-control"
          />
        </div>

        <div class="form-group">
          <label>&nbsp;</label>
          <button @click="resetFilters" class="btn btn-secondary btn-block">
            <i class="fas fa-redo"></i> Reset
          </button>
        </div>
      </div>
    </div>

    <div class="table-card">
      <div class="table-header">
        <h3>Daftar Quality Control</h3>
      </div>
      
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Produk</th>
              <th>Total Qty</th>
              <th>Lolos QC</th>
              <th>Tidak Lolos</th>
              <th>Pass Rate</th>
              <th>Inspector</th>
              <th>Tanggal</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="10" class="text-center">
                <i class="fas fa-spinner fa-spin"></i> Memuat data...
              </td>
            </tr>
            <tr v-else-if="qualityControls.length === 0">
              <td colspan="10" class="text-center">Tidak ada data</td>
            </tr>
            <tr v-else v-for="qc in qualityControls" :key="qc.id">
              <td>#{{ qc.id }}</td>
              <td>
                <div class="product-info">
                  <strong>{{ qc.product?.name }}</strong>
                  <small>SKU: {{ qc.product?.sku }}</small>
                </div>
              </td>
              <td>{{ qc.total_quantity }}</td>
              <td>
                <span class="badge badge-success">{{ qc.passed_quantity }}</span>
              </td>
              <td>
                <span class="badge badge-danger">{{ qc.failed_quantity }}</span>
              </td>
              <td>
                <div class="progress-bar-container">
                  <div class="progress-bar" :style="{ width: qc.pass_rate + '%' }"></div>
                  <span class="progress-text">{{ qc.pass_rate }}%</span>
                </div>
              </td>
              <td>{{ qc.inspector_name || '-' }}</td>
              <td>{{ formatDate(qc.inspection_date) }}</td>
              <td>
                <span :class="['status-badge', 'status-' + qc.status]">
                  {{ qc.status_label }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button 
                    @click="viewDetail(qc)" 
                    class="btn-icon btn-info"
                    title="Detail"
                  >
                    <i class="fas fa-eye"></i>
                  </button>
                  <button 
                    v-if="qc.status === 'pending'"
                    @click="startInspection(qc)" 
                    class="btn-icon btn-warning"
                    title="Mulai Inspeksi"
                  >
                    <i class="fas fa-play"></i>
                  </button>
                  <button 
                    v-if="qc.status === 'in_progress' || qc.status === 'pending'"
                    @click="updateInspection(qc)" 
                    class="btn-icon btn-primary"
                    title="Update"
                  >
                    <i class="fas fa-edit"></i>
                  </button>
                  <button 
                    v-if="qc.status !== 'completed'"
                    @click="deleteQC(qc.id)" 
                    class="btn-icon btn-danger"
                    title="Hapus"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination" v-if="pagination.last_page > 1">
        <button 
          @click="changePage(pagination.current_page - 1)"
          :disabled="pagination.current_page === 1"
          class="btn btn-secondary btn-sm"
        >
          <i class="fas fa-chevron-left"></i>
        </button>
        <span class="pagination-info">
          Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}
        </span>
        <button 
          @click="changePage(pagination.current_page + 1)"
          :disabled="pagination.current_page === pagination.last_page"
          class="btn btn-secondary btn-sm"
        >
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>

    <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Buat Inspeksi Quality Control</h3>
          <button @click="showCreateModal = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Pilih Produksi yang Selesai</label>
            <select v-model="createForm.production_plan_id" class="form-control">
              <option value="">-- Pilih Produksi --</option>
              <option 
                v-for="plan in completedProductions" 
                :key="plan.id" 
                :value="plan.id"
              >
                {{ plan.product?.name }} - {{ plan.produced_quantity }} unit ({{ formatDate(plan.completed_at) }})
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Catatan (Opsional)</label>
            <textarea 
              v-model="createForm.notes" 
              class="form-control"
              rows="3"
              placeholder="Tambahkan catatan inspeksi..."
            ></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showCreateModal = false" class="btn btn-secondary">
            Batal
          </button>
          <button @click="createQC" class="btn btn-primary" :disabled="!createForm.production_plan_id">
            <i class="fas fa-save"></i> Buat
          </button>
        </div>
      </div>
    </div>

    <div v-if="showUpdateModal" class="modal-overlay" @click.self="showUpdateModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Update Hasil Inspeksi</h3>
          <button @click="showUpdateModal = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="info-box">
            <h4>{{ selectedQC?.product?.name }}</h4>
            <p>Total Quantity: <strong>{{ selectedQC?.total_quantity }}</strong></p>
          </div>

          <div class="form-group">
            <label>Jumlah Lolos QC</label>
            <input 
              type="number" 
              v-model.number="updateForm.passed_quantity" 
              class="form-control"
              min="0"
              :max="selectedQC?.total_quantity"
            />
          </div>

          <div class="form-group">
            <label>Jumlah Tidak Lolos QC</label>
            <input 
              type="number" 
              v-model.number="updateForm.failed_quantity" 
              class="form-control"
              min="0"
              :max="selectedQC?.total_quantity"
            />
          </div>

          <div class="form-group">
            <label>Total Terinspeksi</label>
            <input 
              type="text" 
              :value="totalInspected" 
              class="form-control"
              disabled
            />
            <small v-if="totalInspected > selectedQC?.total_quantity" class="text-danger">
              Total tidak boleh melebihi {{ selectedQC?.total_quantity }}
            </small>
          </div>

          <div class="form-group">
            <label>Catatan</label>
            <textarea 
              v-model="updateForm.notes" 
              class="form-control"
              rows="3"
            ></textarea>
          </div>

          <div class="checkbox-group">
            <label>
              <input type="checkbox" v-model="completeInspection" />
              Tandai sebagai selesai (pastikan semua unit sudah diinspeksi)
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showUpdateModal = false" class="btn btn-secondary">
            Batal
          </button>
          <button 
            @click="submitUpdate" 
            class="btn btn-primary"
            :disabled="totalInspected > selectedQC?.total_quantity"
          >
            <i class="fas fa-save"></i> Simpan
          </button>
        </div>
      </div>
    </div>

    <div v-if="showDetailModal" class="modal-overlay" @click.self="showDetailModal = false">
      <div class="modal-content modal-lg">
        <div class="modal-header">
          <h3>Detail Quality Control #{{ selectedQC?.id }}</h3>
          <button @click="showDetailModal = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="detail-grid">
            <div class="detail-item">
              <label>Produk</label>
              <p>{{ selectedQC?.product?.name }}</p>
            </div>
            <div class="detail-item">
              <label>SKU</label>
              <p>{{ selectedQC?.product?.sku }}</p>
            </div>
            <div class="detail-item">
              <label>Total Quantity</label>
              <p>{{ selectedQC?.total_quantity }} unit</p>
            </div>
            <div class="detail-item">
              <label>Lolos QC</label>
              <p class="text-success"><strong>{{ selectedQC?.passed_quantity }} unit</strong></p>
            </div>
            <div class="detail-item">
              <label>Tidak Lolos QC</label>
              <p class="text-danger"><strong>{{ selectedQC?.failed_quantity }} unit</strong></p>
            </div>
            <div class="detail-item">
              <label>Pass Rate</label>
              <p><strong>{{ selectedQC?.pass_rate }}%</strong></p>
            </div>
            <div class="detail-item">
              <label>Inspector</label>
              <p>{{ selectedQC?.inspector_name || '-' }}</p>
            </div>
            <div class="detail-item">
              <label>Tanggal Inspeksi</label>
              <p>{{ formatDateTime(selectedQC?.inspection_date) }}</p>
            </div>
            <div class="detail-item">
              <label>Status</label>
              <p>
                <span :class="['status-badge', 'status-' + selectedQC?.status]">
                  {{ selectedQC?.status_label }}
                </span>
              </p>
            </div>
          </div>

          <div v-if="selectedQC?.notes" class="detail-notes">
            <label>Catatan</label>
            <p>{{ selectedQC?.notes }}</p>
          </div>

          <div class="chart-container">
            <h4>Hasil Inspeksi</h4>
            <div class="chart-bar">
              <div 
                class="chart-segment passed" 
                :style="{ width: selectedQC?.pass_rate + '%' }"
                :title="'Lolos: ' + selectedQC?.passed_quantity"
              >
                <span v-if="selectedQC?.pass_rate > 10">{{ selectedQC?.passed_quantity }}</span>
              </div>
              <div 
                class="chart-segment failed" 
                :style="{ width: selectedQC?.fail_rate + '%' }"
                :title="'Gagal: ' + selectedQC?.failed_quantity"
              >
                <span v-if="selectedQC?.fail_rate > 10">{{ selectedQC?.failed_quantity }}</span>
              </div>
            </div>
            <div class="chart-legend">
              <div class="legend-item">
                <span class="legend-color passed"></span>
                <span>Lolos ({{ selectedQC?.pass_rate }}%)</span>
              </div>
              <div class="legend-item">
                <span class="legend-color failed"></span>
                <span>Tidak Lolos ({{ selectedQC?.fail_rate }}%)</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showDetailModal = false" class="btn btn-secondary">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'QualityControl',
  data() {
    return {
      qualityControls: [],
      completedProductions: [],
      statistics: {},
      loading: false,
      filters: {
        status: '',
        start_date: '',
        end_date: '',
      },
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
      },
      showCreateModal: false,
      showUpdateModal: false,
      showDetailModal: false,
      selectedQC: null,
      completeInspection: false,
      createForm: {
        production_plan_id: '',
        notes: '',
      },
      updateForm: {
        passed_quantity: 0,
        failed_quantity: 0,
        notes: '',
      },
    };
  },
  computed: {
    totalInspected() {
      return (this.updateForm.passed_quantity || 0) + (this.updateForm.failed_quantity || 0);
    }
  },
  mounted() {
    this.fetchQualityControls();
    this.fetchStatistics();
    this.fetchCompletedProductions();
  },
  methods: {
    async fetchQualityControls() {
      this.loading = true;
      try {
        const params = {
          page: this.pagination.current_page,
          per_page: this.pagination.per_page,
          ...this.filters,
        };
        const response = await axios.get('/produksi/quality-control', { params });
        this.qualityControls = response.data.data;
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total,
        };
      } catch (error) {
        console.error('Error fetching quality controls:', error);
        alert('Gagal memuat data quality control');
      } finally {
        this.loading = false;
      }
    },

    async fetchStatistics() {
      try {
        const response = await axios.get('/produksi/quality-control/statistics');
        this.statistics = response.data;
      } catch (error) {
        console.error('Error fetching statistics:', error);
      }
    },

    async fetchCompletedProductions() {
      try {
        const response = await axios.get('/produksi/quality-control/completed-productions');
        this.completedProductions = response.data.data;
      } catch (error) {
        console.error('Error fetching completed productions:', error);
      }
    },

    async createQC() {
      try {
        await axios.post('/produksi/quality-control', this.createForm);
        alert('Quality control berhasil dibuat');
        this.showCreateModal = false;
        this.createForm = { production_plan_id: '', notes: '' };
        this.fetchQualityControls();
        this.fetchStatistics();
        this.fetchCompletedProductions();
      } catch (error) {
        console.error('Error creating QC:', error);
        alert(error.response?.data?.message || 'Gagal membuat quality control');
      }
    },

    async startInspection(qc) {
      if (!confirm('Mulai inspeksi untuk produk ini?')) return;
      
      try {
        await axios.post(`/produksi/quality-control/${qc.id}/start`);
        alert('Inspeksi dimulai');
        this.fetchQualityControls();
        this.fetchStatistics();
      } catch (error) {
        console.error('Error starting inspection:', error);
        alert(error.response?.data?.message || 'Gagal memulai inspeksi');
      }
    },

    updateInspection(qc) {
      this.selectedQC = qc;
      this.updateForm = {
        passed_quantity: qc.passed_quantity || 0,
        failed_quantity: qc.failed_quantity || 0,
        notes: qc.notes || '',
      };
      this.completeInspection = false;
      this.showUpdateModal = true;
    },

    async submitUpdate() {
      try {
        const url = this.completeInspection 
          ? `/produksi/quality-control/${this.selectedQC.id}/complete`
          : `/produksi/quality-control/${this.selectedQC.id}`;
        
        await axios({
          method: this.completeInspection ? 'post' : 'put',
          url,
          data: this.updateForm,
        });

        alert('Hasil inspeksi berhasil diperbarui');
        this.showUpdateModal = false;
        this.fetchQualityControls();
        this.fetchStatistics();
      } catch (error) {
        console.error('Error updating inspection:', error);
        alert(error.response?.data?.message || 'Gagal memperbarui inspeksi');
      }
    },

    viewDetail(qc) {
      this.selectedQC = qc;
      this.showDetailModal = true;
    },

    async deleteQC(id) {
      if (!confirm('Yakin ingin menghapus quality control ini?')) return;
      
      try {
        await axios.delete(`/produksi/quality-control/${id}`);
        alert('Quality control berhasil dihapus');
        this.fetchQualityControls();
        this.fetchStatistics();
      } catch (error) {
        console.error('Error deleting QC:', error);
        alert(error.response?.data?.message || 'Gagal menghapus quality control');
      }
    },

    resetFilters() {
      this.filters = {
        status: '',
        start_date: '',
        end_date: '',
      };
      this.fetchQualityControls();
    },

    changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.pagination.current_page = page;
        this.fetchQualityControls();
      }
    },

    formatDate(date) {
      if (!date) return '-';
      return new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      });
    },

    formatDateTime(date) {
      if (!date) return '-';
      return new Date(date).toLocaleString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    },
  },
};
</script>

<style scoped>
.quality-control-page {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.page-title {
  font-size: 28px;
  font-weight: 600;
  color: #1a202c;
  margin: 0;
}

.page-subtitle {
  color: #718096;
  margin: 4px 0 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
}

.stat-icon.pending { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.stat-icon.progress { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.stat-icon.completed { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.stat-icon.success { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 13px;
  color: #718096;
  margin: 0 0 4px;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #1a202c;
  margin: 0;
}

.filters-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.table-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.table-header {
  padding: 20px;
  border-bottom: 1px solid #e2e8f0;
}

.table-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1a202c;
}

.table-responsive {
  overflow-x: auto;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th {
  background: #f7fafc;
  padding: 12px 16px;
  text-align: left;
  font-size: 13px;
  font-weight: 600;
  color: #4a5568;
  border-bottom: 2px solid #e2e8f0;
  white-space: nowrap;
}

.table td {
  padding: 16px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 14px;
  color: #2d3748;
}

.table tbody tr:hover {
  background: #f7fafc;
}

.product-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.product-info strong {
  color: #1a202c;
}

.product-info small {
  color: #718096;
  font-size: 12px;
}

.progress-bar-container {
  position: relative;
  width: 100px;
  height: 24px;
  background: #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #48bb78, #38a169);
  transition: width 0.3s;
}

.progress-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 11px;
  font-weight: 600;
  color: #1a202c;
}

.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.status-pending {
  background: #fef5e7;
  color: #d97706;
}

.status-in_progress {
  background: #dbeafe;
  color: #2563eb;
}

.status-completed {
  background: #d1fae5;
  color: #059669;
}

.badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
}

.badge-success {
  background: #d1fae5;
  color: #059669;
}

.badge-danger {
  background: #fee2e2;
  color: #dc2626;
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.btn-icon {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  font-size: 14px;
}

.btn-icon.btn-info {
  background: #e0f2fe;
  color: #0284c7;
}

.btn-icon.btn-warning {
  background: #fef3c7;
  color: #d97706;
}

.btn-icon.btn-primary {
  background: #dbeafe;
  color: #2563eb;
}

.btn-icon.btn-danger {
  background: #fee2e2;
  color: #dc2626;
}

.btn-icon:hover {
  opacity: 0.8;
  transform: translateY(-2px);
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-secondary {
  background: #e2e8f0;
  color: #4a5568;
}

.btn-block {
  width: 100%;
  justify-content: center;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 13px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #4a5568;
}

.form-control {
  padding: 10px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-control:disabled {
  background: #f7fafc;
  cursor: not-allowed;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  padding: 20px;
  border-top: 1px solid #e2e8f0;
}

.pagination-info {
  font-size: 14px;
  color: #4a5568;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-content.modal-lg {
  max-width: 800px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #1a202c;
}

.btn-close {
  width: 32px;
  height: 32px;
  border: none;
  background: #f7fafc;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #718096;
  transition: all 0.2s;
}

.btn-close:hover {
  background: #e2e8f0;
  color: #2d3748;
}

.modal-body {
  padding: 24px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px 24px;
  border-top: 1px solid #e2e8f0;
}

.info-box {
  background: #f7fafc;
  border-left: 4px solid #667eea;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.info-box h4 {
  margin: 0 0 8px;
  font-size: 16px;
  color: #1a202c;
}

.info-box p {
  margin: 0;
  font-size: 14px;
  color: #4a5568;
}

.checkbox-group {
  margin-top: 16px;
}

.checkbox-group label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #2d3748;
  cursor: pointer;
}

.checkbox-group input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-item label {
  font-size: 12px;
  color: #718096;
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.5px;
}

.detail-item p {
  margin: 0;
  font-size: 15px;
  color: #1a202c;
  font-weight: 500;
}

.detail-notes {
  background: #f7fafc;
  padding: 16px;
  border-radius: 8px;
  margin-top: 20px;
}

.detail-notes label {
  font-size: 12px;
  color: #718096;
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.5px;
  display: block;
  margin-bottom: 8px;
}

.detail-notes p {
  margin: 0;
  font-size: 14px;
  color: #2d3748;
  line-height: 1.6;
  white-space: pre-wrap;
}

.chart-container {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e2e8f0;
}

.chart-container h4 {
  margin: 0 0 16px;
  font-size: 16px;
  font-weight: 600;
  color: #1a202c;
}

.chart-bar {
  display: flex;
  height: 40px;
  border-radius: 8px;
  overflow: hidden;
  background: #e2e8f0;
  margin-bottom: 12px;
}

.chart-segment {
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.3s;
}

.chart-segment.passed {
  background: linear-gradient(90deg, #48bb78, #38a169);
}

.chart-segment.failed {
  background: linear-gradient(90deg, #f56565, #e53e3e);
}

.chart-legend {
  display: flex;
  gap: 24px;
  justify-content: center;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #4a5568;
}

.legend-color {
  width: 16px;
  height: 16px;
  border-radius: 4px;
}

.legend-color.passed {
  background: linear-gradient(90deg, #48bb78, #38a169);
}

.legend-color.failed {
  background: linear-gradient(90deg, #f56565, #e53e3e);
}

.text-center {
  text-align: center;
}

.text-success {
  color: #059669;
}

.text-danger {
  color: #dc2626;
}

@media (max-width: 768px) {
  .quality-control-page {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .filters-grid {
    grid-template-columns: 1fr;
  }

  .table-responsive {
    overflow-x: scroll;
  }

  .table {
    min-width: 900px;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }

  .modal-content {
    max-width: 100%;
    margin: 0 16px;
  }

  .action-buttons {
    flex-direction: column;
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-content {
  animation: fadeIn 0.3s ease-out;
}
</style>