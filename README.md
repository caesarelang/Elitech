# 🏭 Elitech Production Management System

Sistem ini merupakan aplikasi **manajemen produksi terintegrasi** yang dibagi menjadi dua modul utama:
- **PPIC (Production Planning and Inventory Control)**
- **Produksi (Production Execution)**

Setiap modul memiliki sistem login terpisah dan mendukung dua peran (role): **Manager** dan **Staff**.

---

## 📁 Struktur Modul

| Modul | Deskripsi | Prefix Route | Middleware |
|-------|------------|---------------|-------------|
| **PPIC** | Perencanaan produksi dan kontrol persediaan | `/api/ppic/...` | `auth:sanctum`, `module:ppic` |
| **Produksi** | Pelaksanaan produksi, QC, dan logistik | `/api/produksi/...` | `auth:sanctum`, `module:produksi` |

---

## 🔐 Login Endpoint

| Modul | Endpoint | Method | Deskripsi |
|--------|-----------|---------|------------|
| PPIC | `/api/ppic/login` | `POST` | Login untuk Manager & Staff PPIC |
| Produksi | `/api/produksi/login` | `POST` | Login untuk Manager & Staff Produksi |

> Setelah login berhasil, token Sanctum akan digunakan untuk mengakses semua endpoint terproteksi pada modul masing-masing.

---

## 👥 Akun Default (Seeder)

### 🧩 Modul PPIC
| Role | Email | Password |
|------|--------|-----------|
| Manager | `manager@ppic.com` | `password` |
| Staff | `staff@ppic.com` | `password` |

### ⚙️ Modul Produksi
| Role | Email | Password |
|------|--------|-----------|
| Manager | `manager@production.com` | `password` |
| Staff | `staff@production.com` | `password` |

---

## 🧱 Fitur Utama

### 🔹 Modul PPIC
- Login dan manajemen akun (Manager & Staff)
- Perencanaan produksi (`production-plans`)
- Approval & progress monitoring
- Export laporan produksi
- Dashboard statistik PPIC

### 🔹 Modul Produksi
- Login dan manajemen akun (Manager & Staff)
- Melihat dan menjalankan rencana produksi yang sudah disetujui
- Update progress produksi
- Quality Control (QC)
- Manajemen logistik & stok barang
- Export laporan produksi harian

---

## 🧰 Teknologi yang Digunakan

| Layer | Stack |
|-------|-------|
| **Backend** | Laravel 11 + Sanctum |
| **Frontend** | Vue 3 + Vite |
| **Database** | MySQL |
| **Auth** | Token-based Authentication (Sanctum) |

---

## 🚀 Cara Menjalankan Project

# 1 Clone repository
git clone <repository-url>
cd <project-folder>

# 2 Install dependency Laravel (Backend)
composer install
cp .env.example .env
php artisan key:generate

# 3 Migrasi dan seed database
php artisan migrate --seed

# 4 Install dependency Frontend (Vue)
npm install

# 5 Jalankan backend dan frontend bersamaan
# (gunakan tmux / & untuk menjalankan paralel)
php artisan serve & npm run dev


