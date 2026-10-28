# Hitung Gamis: Aplikasi Kalkulator Kasir

Aplikasi untuk menghitung belanjaan dan mencatat riwayat penjualan jubah dalam 3 kategori.

## Prasyarat

- PHP 8.2+
- Laravel 13
- MySQL 8.0+
- Composer

## Persiapan

### 1. Buat database

Buka MySQL client (misalnya HeidiSQL di Laragon) dan jalankan:

```sql
CREATE DATABASE IF NOT EXISTS hitung_gamis      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS hitung_gamis_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Konfigurasi `.env` dan `.env.testing`

Edit `.env`:
```dotenv
APP_NAME="Hitung Gamis"
APP_ENV=local
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hitung_gamis
DB_USERNAME=root
DB_PASSWORD=(password Laragon)
APP_TIMEZONE=Asia/Jakarta
ADMIN_EMAIL=
ADMIN_PASSWORD=
```

Buat `.env.testing` (salin dari `.env.testing.example`, isi dengan nilai dari `.env`):
```dotenv
APP_ENV=testing
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hitung_gamis_test
DB_USERNAME=root
DB_PASSWORD=(password Laragon)
```

### 3. Install dependencies dan setup

```bash
composer install
php artisan key:generate
```

### 4. Migrasi tabel

```bash
php artisan migrate
```

### 5. Seed kategori dan ukuran

```bash
php artisan db:seed
```

### 6. Buat akun admin

Isi `ADMIN_EMAIL` dan `ADMIN_PASSWORD` (minimum 12 karakter) di `.env`, lalu:

```bash
php artisan db:seed --class=AdminSeeder
```

### 7. Jalankan aplikasi

```bash
php artisan serve
```

Buka `http://localhost:8000` di browser.

## Testing

Persiapan testing (hanya sekali):

```bash
php artisan migrate:fresh --seed --env=testing
```

Jalankan test:

```bash
php artisan test
```

## Fitur utama

- **Kasir**: pilih kategori, tekan +/- per ukuran, lihat total, simpan pesanan
- **Riwayat pesanan**: daftar pesanan, filter tanggal dan status, detail, batalkan
- **Laporan harian**: rekap penjualan per hari
- **Admin - Harga**: ubah harga all/one per kategori, toggle aktif/nonaktif, riwayat
- **Admin - CRUD**: kelola kategori, ukuran, riwayat harga per ukuran

## Arsitektur database

| Komponen | Deskripsi |
|---|---|
| `categories` | Kategori jubah: anak, dewasa, luar |
| `sizes` | Ukuran per kategori dengan harga |
| `orders` | Pesanan dengan total harga dan status (selesai/batal) |
| `order_items` | Item dalam pesanan, harga satuan dicopy dari `sizes` |
| `price_logs` | Log otomatis setiap kali harga berubah (trigger) |

## Procedure dan Function

| Nama | Fungsi |
|---|---|
| `fn_subtotal(qty, harga)` | Hitung subtotal satu item |
| `fn_total_pesanan(order_id)` | Hitung total pesanan |
| `fn_format_kode(id, tanggal)` | Format kode pesanan: `INV-YYYYMMDD-NNNNN` |
| `sp_simpan_pesanan(nama, catatan, items, @order_id)` | Simpan pesanan (atomic transaction) |
| `sp_batalkan_pesanan(order_id, alasan)` | Batalkan pesanan (ubah status, jangan hapus) |
| `sp_rekap_harian(dari, sampai)` | Rekap penjualan per hari (hanya status selesai) |
| `sp_ubah_harga_massal(category_id, selisih)` | Naik/turun harga satu kategori |
| `trg_sizes_log_harga` | Trigger: log otomatis ke `price_logs` saat harga berubah |

## Stack

- **Backend**: Laravel 13, PHP 8.3
- **Database**: MySQL 8.0 dengan stored procedure dan trigger
- **Frontend**: Blade, Alpine.js (CDN), CSS custom (`public/css/gamis.css`)
- **Auth**: Middleware sederhana (role: user, admin)
- **Testing**: Pest/PHPUnit dengan database MySQL test

## Catatan

- Aplikasi memakai timezone **Asia/Jakarta** (UTC+7).
- Zona waktu MySQL koneksi juga di-set ke `+07:00`.
- Semua pesanan disimpan lewat stored procedure dengan `START TRANSACTION` dan `ROLLBACK` otomatis jika ada error.
- Harga di `order_items.harga_satuan` **dicopy** saat transaksi, tidak referensi `sizes.harga`.
- Pesanan tidak pernah dihapus, hanya dibatalkan.
- Laporan hanya menghitung pesanan berstatus `selesai`.
