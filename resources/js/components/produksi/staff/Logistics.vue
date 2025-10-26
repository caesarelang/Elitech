<template>
  <div class="logistics-container">
    <div class="header">
      <div>
        <h1>📦 Manajemen Logistik & Gudang</h1>
        <p class="subtitle">Kelola stok produk di berbagai gudang</p>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">📊</div>
        <div class="stat-info">
          <div class="stat-label">Total Item</div>
          <div class="stat-value">{{ statistics.totalItems }}</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div class="stat-info">
          <div class="stat-label">Stok Tersedia</div>
          <div class="stat-value">{{ statistics.inStock }}</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon orange">⚠️</div>
        <div class="stat-info">
          <div class="stat-label">Stok Menipis</div>
          <div class="stat-value">{{ statistics.lowStock }}</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red">❌</div>
        <div class="stat-info">
          <div class="stat-label">Stok Habis</div>
          <div class="stat-value">{{ statistics.outOfStock }}</div>
        </div>
      </div>
    </div>

    <div class="filters">
      <input
        v-model="filters.search"
        type="text"
        placeholder="🔍 Cari nama produk atau SKU..."
        class="search-input"
        @input="debounceSearch"
      />
      <select v-model="filters.warehouse" class="filter-select" @change="fetchLogistics">
        <option value="">🏢 Semua Gudang</option>
        <option v-for="wh in warehouses" :key="wh.warehouse_name" :value="wh.warehouse_name">
          {{ wh.warehouse_name }}
        </option>
      </select>
      <select v-model="filters.status" class="filter-select" @change="fetchLogistics">
        <option value="">📊 Semua Status</option>
        <option value="in_stock">✅ Tersedia</option>
        <option value="low_stock">⚠️ Stok Menipis</option>
        <option value="out_of_stock">❌ Habis</option>
      </select>
      <button @click="showSyncModal = true" class="btn-secondary">
        🔄 Sync dari QC
      </button>
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Memuat data...</p>
    </div>

    <div v-else-if="logistics.length > 0" class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Produk</th>
            <th>Gudang</th>
            <th>Lokasi</th>
            <th>Stok</th>
            <th>Min. Stok</th>
            <th>Status</th>
            <th>Terakhir Update</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in logistics" :key="item.id">
            <td>
              <div class="product-info">
                <div class="product-name">{{ item.product_name }}</div>
                <div class="product-sku">{{ item.product_sku }}</div>
              </div>
            </td>
            <td>
              <div class="warehouse-badge">{{ item.warehouse_name }}</div>
            </td>
            <td>{{ item.warehouse_location || '-' }}</td>
            <td>
              <div class="stock-value">{{ item.stock }} unit</div>
            </td>
            <td>{{ item.minimum_stock }} unit</td>
            <td>
              <span :class="['status-badge', item.stock_status]">
                {{ item.stock_status_label }}
              </span>
            </td>
            <td>
              <div class="update-info">
                <div>{{ formatDate(item.updated_at) }}</div>
                <div class="updater">{{ item.updater_name }}</div>
              </div>
            </td>
            <td>
              <div class="action-buttons">
                <button @click="viewDetail(item)" class="btn-icon" title="Detail">
                  👁️
                </button>
                <button @click="editItem(item)" class="btn-icon" title="Edit">
                  ✏️
                </button>
                <button @click="adjustStockModal(item)" class="btn-icon" title="Sesuaikan Stok">
                  🔢
                </button>
                <button @click="deleteItem(item)" class="btn-icon danger" title="Hapus">
                  🗑️
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="empty-state">
      <div class="empty-icon">📦</div>
      <h3>Belum Ada Data Logistik</h3>
      <p>Mulai sync dari Quality Control</p>
    </div>

    <div v-if="showCreateModal || showEditModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ showEditModal ? '✏️ Edit Data Gudang' : '➕ Tambah Data Gudang' }}</h2>
          <button @click="closeModal" class="close-btn">✕</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <div class="form-group">
              <label>Produk *</label>
              <select v-model="form.product_id" required class="form-input">
                <option value="">Pilih Produk</option>
                <option v-for="product in products" :key="product.id" :value="product.id">
                  {{ product.name }} ({{ product.sku }})
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Nama Gudang *</label>
              <input
                v-model="form.warehouse_name"
                type="text"
                required
                class="form-input"
                placeholder="Contoh: Gudang Utama"
              />
            </div>

            <div class="form-group">
              <label>Lokasi Gudang</label>
              <input
                v-model="form.warehouse_location"
                type="text"
                class="form-input"
                placeholder="Contoh: Jl. Raya Industri No. 123"
              />
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Stok *</label>
                <input
                  v-model.number="form.stock"
                  type="number"
                  required
                  min="0"
                  class="form-input"
                  placeholder="0"
                />
              </div>

              <div class="form-group">
                <label>Minimum Stok *</label>
                <input
                  v-model.number="form.minimum_stock"
                  type="number"
                  required
                  min="0"
                  class="form-input"
                  placeholder="0"
                />
              </div>
            </div>

            <div class="form-group">
              <label>Catatan</label>
              <textarea
                v-model="form.notes"
                class="form-input"
                rows="3"
                placeholder="Catatan tambahan..."
              ></textarea>
            </div>

            <div class="modal-footer">
              <button type="button" @click="closeModal" class="btn-secondary">
                Batal
              </button>
              <button type="submit" class="btn-primary" :disabled="submitting">
                {{ submitting ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div v-if="showAdjustModal" class="modal-overlay" @click.self="showAdjustModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>🔢 Sesuaikan Stok</h2>
          <button @click="showAdjustModal = false" class="close-btn">✕</button>
        </div>
        <div class="modal-body">
          <div class="current-stock-info">
            <div>
              <strong>Produk:</strong> {{ selectedItem?.product_name }}
            </div>
            <div>
              <strong>Stok Saat Ini:</strong> {{ selectedItem?.stock }} unit
            </div>
          </div>

          <form @submit.prevent="submitAdjustStock">
            <div class="form-group">
              <label>Penyesuaian *</label>
              <input
                v-model.number="adjustForm.adjustment"
                type="number"
                required
                class="form-input"
                placeholder="Contoh: +50 atau -20"
              />
              <small>Gunakan angka positif untuk menambah, negatif untuk mengurangi</small>
            </div>

            <div class="form-group">
              <label>Alasan *</label>
              <textarea
                v-model="adjustForm.reason"
                required
                class="form-input"
                rows="3"
                placeholder="Jelaskan alasan penyesuaian stok..."
              ></textarea>
            </div>

            <div class="result-stock">
              <strong>Stok Setelah Penyesuaian:</strong>
              {{ (selectedItem?.stock || 0) + (adjustForm.adjustment || 0) }} unit
            </div>

            <div class="modal-footer">
              <button type="button" @click="showAdjustModal = false" class="btn-secondary">
                Batal
              </button>
              <button type="submit" class="btn-primary" :disabled="submitting">
                {{ submitting ? 'Menyimpan...' : 'Sesuaikan Stok' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div v-if="showSyncModal" class="modal-overlay" @click.self="showSyncModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>🔄 Sync dari Quality Control</h2>
          <button @click="showSyncModal = false" class="close-btn">✕</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitBulkSync">
            <div class="form-group">
              <label>Nama Gudang *</label>
              <input
                v-model="syncForm.warehouse_name"
                type="text"
                required
                class="form-input"
                placeholder="Contoh: Gudang Utama"
              />
            </div>

            <div class="form-group">
              <label>Lokasi Gudang</label>
              <input
                v-model="syncForm.warehouse_location"
                type="text"
                class="form-input"
                placeholder="Contoh: Jl. Raya Industri No. 123"
              />
            </div>

            <div class="info-box">
              <p>
                ℹ️ Fitur ini akan menyinkronkan semua produk yang telah lulus Quality Control
                ke gudang yang ditentukan.
              </p>
            </div>

            <div class="modal-footer">
              <button type="button" @click="showSyncModal = false" class="btn-secondary">
                Batal
              </button>
              <button type="submit" class="btn-primary" :disabled="submitting">
                {{ submitting ? 'Menyinkronkan...' : 'Sync Sekarang' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div v-if="showDetailModal" class="modal-overlay" @click.self="showDetailModal = false">
      <div class="modal modal-large">
        <div class="modal-header">
          <h2>📦 Detail Logistik</h2>
          <button @click="showDetailModal = false" class="close-btn">✕</button>
        </div>
        <div class="modal-body">
          <div v-if="selectedItem" class="detail-grid">
            <div class="detail-section">
              <h3>Informasi Produk</h3>
              <div class="detail-item">
                <span>Nama Produk:</span>
                <strong>{{ selectedItem.product_name }}</strong>
              </div>
              <div class="detail-item">
                <span>SKU:</span>
                <strong>{{ selectedItem.product_sku }}</strong>
              </div>
            </div>

            <div class="detail-section">
              <h3>Informasi Gudang</h3>
              <div class="detail-item">
                <span>Nama Gudang:</span>
                <strong>{{ selectedItem.warehouse_name }}</strong>
              </div>
              <div class="detail-item">
                <span>Lokasi:</span>
                <strong>{{ selectedItem.warehouse_location || '-' }}</strong>
              </div>
            </div>

            <div class="detail-section">
              <h3>Stok</h3>
              <div class="detail-item">
                <span>Stok Saat Ini:</span>
                <strong>{{ selectedItem.stock }} unit</strong>
              </div>
              <div class="detail-item">
                <span>Minimum Stok:</span>
                <strong>{{ selectedItem.minimum_stock }} unit</strong>
              </div>
              <div class="detail-item">
                <span>Status:</span>
                <span :class="['status-badge', selectedItem.stock_status]">
                  {{ selectedItem.stock_status_label }}
                </span>
              </div>
            </div>

            <div class="detail-section full-width">
              <h3>Informasi Tambahan</h3>
              <div class="detail-item">
                <span>Terakhir Update:</span>
                <strong>{{ formatDate(selectedItem.updated_at) }}</strong>
              </div>
              <div class="detail-item">
                <span>Diupdate Oleh:</span>
                <strong>{{ selectedItem.updater_name }}</strong>
              </div>
              <div class="detail-item" v-if="selectedItem.notes">
                <span>Catatan:</span>
                <strong>{{ selectedItem.notes }}</strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Logistics',
  data() {
    return {
      loading: false,
      submitting: false,
      logistics: [],
      products: [],
      warehouses: [],
      selectedItem: null,
      
      statistics: {
        totalItems: 0,
        inStock: 0,
        lowStock: 0,
        outOfStock: 0,
      },
      
      filters: {
        search: '',
        warehouse: '',
        status: '',
      },
      
      showCreateModal: false,
      showEditModal: false,
      showAdjustModal: false,
      showSyncModal: false,
      showDetailModal: false,
      
      form: {
        product_id: '',
        warehouse_name: '',
        warehouse_location: '',
        stock: 0,
        minimum_stock: 10,
        notes: '',
      },
      
      adjustForm: {
        adjustment: 0,
        reason: '',
      },
      
      syncForm: {
        warehouse_name: '',
        warehouse_location: '',
      },
      
      searchTimeout: null,
    };
  },
  
  mounted() {
    this.fetchLogistics();
    this.fetchProducts();
    this.fetchWarehouses();
  },
  
  methods: {
    async fetchLogistics() {
      this.loading = true;
      try {
        const params = new URLSearchParams();
        if (this.filters.search) params.append('search', this.filters.search);
        if (this.filters.warehouse) params.append('warehouse', this.filters.warehouse);
        if (this.filters.status) params.append('status', this.filters.status);
        
        const response = await axios.get(`/produksi/logistics?${params}`);
        this.logistics = response.data.data;
        this.calculateStatistics();
      } catch (error) {
        console.error('Error fetching logistics:', error);
        alert('Gagal memuat data logistik');
      } finally {
        this.loading = false;
      }
    },
    
    async fetchProducts() {
      try {
        const response = await axios.get('/produksi/production-plans');
        const uniqueProducts = [];
        const productIds = new Set();
        
        response.data.data.forEach(plan => {
          if (plan.product && !productIds.has(plan.product.id)) {
            productIds.add(plan.product.id);
            uniqueProducts.push(plan.product);
          }
        });
        
        this.products = uniqueProducts;
      } catch (error) {
        console.error('Error fetching products:', error);
      }
    },
    
    async fetchWarehouses() {
      try {
        const response = await axios.get('/produksi/logistics/warehouses');
        this.warehouses = response.data;
      } catch (error) {
        console.error('Error fetching warehouses:', error);
      }
    },
    
    calculateStatistics() {
      this.statistics.totalItems = this.logistics.length;
      this.statistics.inStock = this.logistics.filter(
        item => item.stock_status === 'in_stock'
      ).length;
      this.statistics.lowStock = this.logistics.filter(
        item => item.stock_status === 'low_stock'
      ).length;
      this.statistics.outOfStock = this.logistics.filter(
        item => item.stock_status === 'out_of_stock'
      ).length;
    },
    
    debounceSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.fetchLogistics();
      }, 500);
    },
    
    async submitForm() {
      this.submitting = true;
      try {
        if (this.showEditModal) {
          await axios.put(`/produksi/logistics/${this.selectedItem.id}`, this.form);
          alert('Data gudang berhasil diperbarui');
        } else {
          await axios.post('/produksi/logistics', this.form);
          alert('Data gudang berhasil ditambahkan');
        }
        this.closeModal();
        this.fetchLogistics();
        this.fetchWarehouses();
      } catch (error) {
        console.error('Error saving logistics:', error);
        alert(error.response?.data?.message || 'Gagal menyimpan data');
      } finally {
        this.submitting = false;
      }
    },
    
    async submitAdjustStock() {
      if (!this.adjustForm.adjustment) {
        alert('Masukkan jumlah penyesuaian');
        return;
      }
      
      this.submitting = true;
      try {
        await axios.post(
          `/produksi/logistics/${this.selectedItem.id}/adjust-stock`,
          this.adjustForm
        );
        alert('Stok berhasil disesuaikan');
        this.showAdjustModal = false;
        this.resetAdjustForm();
        this.fetchLogistics();
      } catch (error) {
        console.error('Error adjusting stock:', error);
        alert(error.response?.data?.message || 'Gagal menyesuaikan stok');
      } finally {
        this.submitting = false;
      }
    },
    
    async submitBulkSync() {
      this.submitting = true;
      try {
        const response = await axios.post(
          '/produksi/logistics/bulk-sync-from-qc',
          this.syncForm
        );
        alert(response.data.message || 'Sinkronisasi berhasil');
        this.showSyncModal = false;
        this.resetSyncForm();
        this.fetchLogistics();
      } catch (error) {
        console.error('Error syncing from QC:', error);
        alert(error.response?.data?.message || 'Gagal menyinkronkan data');
      } finally {
        this.submitting = false;
      }
    },
    
    editItem(item) {
      this.selectedItem = item;
      this.form = {
        product_id: item.product_id,
        warehouse_name: item.warehouse_name,
        warehouse_location: item.warehouse_location,
        stock: item.stock,
        minimum_stock: item.minimum_stock,
        notes: item.notes,
      };
      this.showEditModal = true;
    },
    
    adjustStockModal(item) {
      this.selectedItem = item;
      this.resetAdjustForm();
      this.showAdjustModal = true;
    },
    
    viewDetail(item) {
      this.selectedItem = item;
      this.showDetailModal = true;
    },
    
    async deleteItem(item) {
      if (!confirm(`Hapus data gudang untuk ${item.product_name}?`)) return;
      
      try {
        await axios.delete(`/produksi/logistics/${item.id}`);
        alert('Data gudang berhasil dihapus');
        this.fetchLogistics();
      } catch (error) {
        console.error('Error deleting logistics:', error);
        alert('Gagal menghapus data');
      }
    },
    
    closeModal() {
      this.showCreateModal = false;
      this.showEditModal = false;
      this.selectedItem = null;
      this.resetForm();
    },
    
    resetForm() {
      this.form = {
        product_id: '',
        warehouse_name: '',
        warehouse_location: '',
        stock: 0,
        minimum_stock: 10,
        notes: '',
      };
    },
    
    resetAdjustForm() {
      this.adjustForm = {
        adjustment: 0,
        reason: '',
      };
    },
    
    resetSyncForm() {
      this.syncForm = {
        warehouse_name: '',
        warehouse_location: '',
      };
    },
    
    formatDate(date) {
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
.logistics-container {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.header h1 {
  font-size: 28px;
  color: #1a202c;
  margin: 0 0 5px 0;
}

.subtitle {
  color: #718096;
  margin: 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.stat-icon.blue { background: #e3f2fd; }
.stat-icon.green { background: #e8f5e9; }
.stat-icon.orange { background: #fff3e0; }
.stat-icon.red { background: #ffebee; }

.stat-label {
  font-size: 14px;
  color: #718096;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #1a202c;
}

.filters {
  display: flex;
  gap: 15px;
  margin-bottom: 25px;
  flex-wrap: wrap;
}

.search-input,
.filter-select {
  flex: 1;
  min-width: 200px;
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
}

.search-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #3182ce;
}

.table-container {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  background: #f7fafc;
  padding: 16px;
  text-align: left;
  font-weight: 600;
  color: #2d3748;
  border-bottom: 2px solid #e2e8f0;
}

.data-table td {
  padding: 16px;
  border-bottom: 1px solid #e2e8f0;
}

.data-table tbody tr:hover {
  background: #f7fafc;
}

.product-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.product-name {
  font-weight: 600;
  color: #2d3748;
}

.product-sku {
  font-size: 12px;
  color: #718096;
}

.warehouse-badge {
  display: inline-block;
  padding: 6px 12px;
  background: #e3f2fd;
  color: #1976d2;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
}

.stock-value {
  font-weight: 600;
  color: #2d3748;
  font-size: 15px;
}

.status-badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}

.status-badge.in_stock {
  background: #e8f5e9;
  color: #2e7d32;
}

.status-badge.low_stock {
  background: #fff3e0;
  color: #f57c00;
}

.status-badge.out_of_stock {
  background: #ffebee;
  color: #c62828;
}

.update-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 13px;
}

.updater {
  color: #718096;
  font-size: 12px;
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.btn-icon {
  padding: 8px;
  border: none;
  background: #f7fafc;
  border-radius: 6px;
  cursor: pointer;
  font-size: 16px;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #e2e8f0;
  transform: scale(1.1);
}

.btn-icon.danger:hover {
  background: #ffebee;
}

.btn-primary {
  padding: 12px 24px;
  background: #3182ce;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #2c5282;
}

.btn-primary:disabled {
  background: #cbd5e0;
  cursor: not-allowed;
}

.btn-secondary {
  padding: 12px 24px;
  background: white;
  color: #3182ce;
  border: 2px solid #3182ce;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #ebf8ff;
}

.loading {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #e2e8f0;
  border-top-color: #3182ce;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 80px 20px;
  background: white;
  border-radius: 12px;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 20px;
}

.empty-state h3 {
  color: #2d3748;
  margin-bottom: 10px;
}

.empty-state p {
  color: #718096;
  margin-bottom: 30px;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.modal-large {
  max-width: 900px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 2px solid #e2e8f0;
}

.modal-header h2 {
  margin: 0;
  font-size: 22px;
  color: #1a202c;
}

.close-btn {
  background: none;
  border: none;
  font-size: 28px;
  color: #718096;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #f7fafc;
  color: #2d3748;
}

.modal-body {
  padding: 24px;
}

.modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 24px;
  border-top: 2px solid #e2e8f0;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #2d3748;
  font-size: 14px;
}

.form-input {
  width: 100%;
  padding: 12px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #3182ce;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.form-group small {
  display: block;
  margin-top: 6px;
  color: #718096;
  font-size: 12px;
}

.current-stock-info {
  background: #f7fafc;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.current-stock-info div {
  margin-bottom: 8px;
}

.current-stock-info div:last-child {
  margin-bottom: 0;
}

.result-stock {
  background: #e3f2fd;
  padding: 16px;
  border-radius: 8px;
  margin-top: 20px;
  font-size: 16px;
  text-align: center;
}

.info-box {
  background: #e3f2fd;
  border-left: 4px solid #3182ce;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.info-box p {
  margin: 0;
  color: #1976d2;
  font-size: 14px;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
}

.detail-section {
  background: #f7fafc;
  padding: 20px;
  border-radius: 8px;
}

.detail-section.full-width {
  grid-column: 1 / -1;
}

.detail-section h3 {
  margin: 0 0 16px 0;
  font-size: 16px;
  color: #2d3748;
  padding-bottom: 12px;
  border-bottom: 2px solid #e2e8f0;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #e2e8f0;
}

.detail-item:last-child {
  border-bottom: none;
}

.detail-item span:first-child {
  color: #718096;
  font-size: 14px;
}

.detail-item strong {
  color: #2d3748;
  text-align: right;
}

@media (max-width: 768px) {
  .header {
    flex-direction: column;
    align-items: flex-start;
    gap: 20px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .filters {
    flex-direction: column;
  }

  .search-input,
  .filter-select {
    width: 100%;
  }

  .table-container {
    overflow-x: auto;
  }

  .data-table {
    min-width: 1000px;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }

  .modal {
    max-width: 100%;
    margin: 10px;
  }
}
</style>