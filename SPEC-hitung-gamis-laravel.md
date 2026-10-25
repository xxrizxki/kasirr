# SPEC: Aplikasi "Hitung Gamis" (Laravel + MySQL), versi 2: tema Night City

Dokumen ini adalah instruksi kerja untuk agent. Baca seluruhnya sebelum menulis kode, lalu kerjakan **per fase** sesuai urutan di bagian 3. Jangan lompat fase, dan jalankan pengecekan di akhir tiap fase.

**Catatan versi 2:** tampilan sudah final dan disetujui pemilik (tema Night City). Bagian 10, Lampiran A (CSS lengkap), dan Lampiran B (kerangka Blade) menggantikan semua keterangan tampilan versi sebelumnya.

---

## 1. Konteks dan tujuan

Pemilik toko kecil menjual jubah dalam 3 kategori. Catatan harganya selama ini ditulis tangan di buku. Aplikasi ini membantu **menghitung belanjaan pembeli** dan **menyimpan riwayat penjualan**.

Kategori dan ukuran:

| Kategori | Kode | Ukuran |
|---|---|---|
| Jubah anak | `anak` | No 2, 4, 6, 8, 10, 12, 14 (pakai nomor) |
| Jubah dewasa | `dewasa` | M, L, XL, XXL |
| Jubah luar | `luar` | M, L, XL |

> **Catatan penamaan:** nama aplikasi adalah **Hitung Gamis** (database `hitung_gamis` dan `hitung_gamis_test`, file CSS `gamis.css`). Nama kategori produk di tabel di atas (Jubah anak, Jubah dewasa, Jubah luar), kode kategorinya, dan data seeder **tidak berubah**.

Setiap ukuran punya harga sendiri (lihat seeder, bagian 5).

Fungsi utama:
1. **Kalkulator kasir**: pilih kategori, tekan +/- per ukuran, total langsung berubah, lalu "Simpan pesanan".
2. **CRUD master data**: kategori, ukuran, dan harga.
3. **Riwayat pesanan**: daftar, detail, dan pembatalan pesanan.
4. **Laporan**: rekap penjualan harian.
5. Aspek database yang **wajib** muncul: relasi antar tabel, **transaction (commit/rollback)**, **stored procedure**, **function**, dan **trigger**.

Bahasa antarmuka: **Bahasa Indonesia**. Format uang: `Rp 36.500` (pemisah titik).

---

## 2. Stack dan prasyarat

- PHP 8.2+, Laravel 11 (atau 12 bila sudah terpasang), Composer.
- **MySQL 8.0+** wajib (memakai `JSON_TABLE`, stored procedure, dan trigger). Aturan database lengkap ada di bagian 2.1.
- Frontend: Blade + Alpine.js (CDN) + CSS biasa di `public/css/gamis.css`. Tidak perlu React/Vue.
- Auth admin: opsional (fase 9). Kasir (halaman kalkulator) boleh terbuka dulu.

### 2.1 Database: wajib MySQL

- Server: **MySQL 8.0+** dari environment lokal yang sudah tersedia, idealnya **MySQL milik Laragon** yang sudah terpasang. Jangan menginstal, menjalankan, atau membuat server database lain.
- Dua database terpisah di server yang sama:

| Fungsi | Nama database | Dibaca dari |
|---|---|---|
| Aplikasi | `hitung_gamis` | `.env` |
| Testing | `hitung_gamis_test` | `.env.testing` dan `phpunit.xml` (bagian 11.1) |

- Koneksi memakai `.env` dengan `DB_CONNECTION=mysql` dan variabel koneksi lain (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
- **Dilarang tanpa persetujuan pemilik:** SQLite, `:memory:`, database berbasis file, atau server database alternatif. Jika server yang aktif ternyata MariaDB atau MySQL di bawah 8.0, **berhenti dan lapor**; dokumen ini tidak ditulis untuk keduanya.
- Folder `database/` di Laravel tetap dipakai untuk migration dan seeder. **Data aplikasi hanya disimpan di server MySQL**, tidak ada file `.sqlite` yang dipakai. Jika sudah ada `database/database.sqlite` bawaan Laravel, biarkan saja (jangan dipakai, jangan dihapus).

### 2.2 Prosedur keamanan (Fase 0, wajib sebelum mengubah apa pun)

Urutan kerja:

1. Baca `AGENTS.md` dan `CLAUDE.md` (dan `boost.json` bila relevan). Ikuti aturannya. Jika bertentangan dengan dokumen ini, **berhenti dan lapor**.
2. Periksa kondisi project **tanpa mengubah apa pun**:

```powershell
php -v
php artisan --version
git status                       # bila project memakai Git
php artisan db:show --counts     # server, versi, nama database aktif, daftar tabel (tidak menampilkan password)
php artisan migrate:status       # hanya membaca
php artisan route:list
```

Bila server MySQL sudah berjalan, jalankan juga (hanya membaca):

```sql
SELECT VERSION();
SHOW DATABASES LIKE 'hitung_gamis%';
SHOW PROCEDURE STATUS WHERE Db = 'hitung_gamis';
SHOW FUNCTION STATUS WHERE Db = 'hitung_gamis';
SHOW TRIGGERS FROM hitung_gamis;
```

3. Verifikasi dan catat: versi PHP dan Laravel; status MySQL (aktif, versi 8.0+, bukan MariaDB); nilai `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`; nama database yang aktif sekarang; apakah `hitung_gamis` dan `hitung_gamis_test` sudah ada; tabel yang sudah ada dan apakah ada yang bentrok dengan `categories`, `sizes`, `orders`, `order_items`, `price_logs`; routine atau trigger yang bentrok; isi `phpunit.xml` bagian `<php>`; apakah `.env.testing` sudah ada; status Git.
4. **Jangan menampilkan atau menyalin password** dari `.env` ke laporan, log, atau pesan commit. Tulis `DB_PASSWORD: (disembunyikan)`.
5. Kirim **Laporan Fase 0** berisi poin 3 dan daftar risiko atau konflik yang ditemukan, lalu **tunggu persetujuan pemilik** sebelum masuk Fase 1.
6. Bila project memakai Git: buat branch kerja baru (misalnya `fitur/hitung-gamis`) sebelum perubahan pertama. Jangan commit atau push tanpa diminta.

Bila ditemukan konflik dengan project yang sudah ada (nama tabel sama, `AGENTS.md` melarang sesuatu, database aktif bukan yang diharapkan, dan sejenisnya), **hentikan bagian yang berisiko**, laporkan masalahnya, dan lanjutkan hanya bagian yang aman setelah pemilik menjawab.

### 2.3 Aturan perintah

| Kategori | Perintah / tindakan |
|---|---|
| **Boleh** (hanya membaca atau terbatas pada database test) | `php -v`, `php artisan --version`, `db:show`, `migrate:status`, `route:list`, `php artisan test` (setelah guard di 11.1 terpasang), `migrate:fresh --seed --env=testing` **hanya** setelah `db:show --env=testing` menampilkan `hitung_gamis_test` |
| **Butuh konfirmasi eksplisit dari pemilik** | `CREATE DATABASE`; `php artisan migrate` pertama pada `hitung_gamis`; `php artisan db:seed` pada `hitung_gamis`; mengubah `.env` yang sudah berfungsi; mengubah `phpunit.xml`, `.gitignore`, atau file bawaan project lain; membuat branch atau commit Git |
| **Dilarang** tanpa konfirmasi khusus dan eksplisit untuk perintah itu | `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `migrate:rollback`, `db:wipe` pada `hitung_gamis` atau database lain yang berpotensi berisi data penting; `DROP`, `TRUNCATE`, atau `DELETE` manual di luar test; opsi `--force` pada database aplikasi; mengedit atau menghapus migration yang sudah ada; menghapus tabel, data, atau fitur yang sudah ada |
| **Dilarang mutlak** | SQLite atau database berbasis file; menginstal atau menjalankan server database lain; menampilkan atau menyalin password |

Semua migration milik pekerjaan ini adalah **file baru**. Migration lama tidak diubah.

### 2.4 Konfigurasi `.env` dan `.env.testing`

Contoh bentuk (isi nilai sesuai Laragon di mesin pemilik; **jangan menaruh password sungguhan di dokumen atau laporan**):

```dotenv
# .env  (jangan ditimpa bila sudah berfungsi; perubahan DB_DATABASE hanya dengan izin)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hitung_gamis
DB_USERNAME=<sesuai Laragon>
DB_PASSWORD=<sesuai Laragon>
```

```dotenv
# .env.testing  (file baru; salin host, user, password dari .env langsung di file, bukan lewat laporan)
APP_ENV=testing
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hitung_gamis_test
DB_USERNAME=<sama dengan .env>
DB_PASSWORD=<sama dengan .env>
```

Pastikan `.env.testing` tidak ikut ter-commit (periksa `.gitignore`; bila perlu, usulkan perubahannya ke pemilik).

Membuat database (jelaskan targetnya ke pemilik dan tunggu "ya" sebelum menjalankan; bisa lewat HeidiSQL di Laragon atau `mysql -u <user> -p`):

```sql
CREATE DATABASE IF NOT EXISTS hitung_gamis      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS hitung_gamis_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Pastikan user MySQL punya hak `CREATE ROUTINE`, `ALTER ROUTINE`, `EXECUTE`, dan `TRIGGER` pada kedua database. Bila migrasi gagal membuat procedure, cek hak ini lebih dulu.

---

## 3. Urutan kerja agent (wajib diikuti)

| Fase | Pekerjaan | Cek selesai |
|---|---|---|
| 0 | Prosedur keamanan bagian 2.2: baca `AGENTS.md` dan `CLAUDE.md`, periksa PHP/Laravel, MySQL, `.env`, database aktif, dan tabel yang ada | Laporan Fase 0 (tanpa password) dikirim **dan disetujui pemilik** sebelum mengubah apa pun |
| 1 | Siapkan `.env.testing`, `phpunit.xml`, dan guard test (bagian 11.1); lalu migrasi tabel (bagian 4) | `db:show --env=testing` menampilkan `hitung_gamis_test`; `migrate:fresh --env=testing` sukses di database test |
| 2 | Migrasi function, procedure, trigger (bagian 6) | Query uji di bagian 6.5 berhasil di `hitung_gamis_test` |
| 3 | Seeder (bagian 5) | 14 baris di tabel `sizes` pada `hitung_gamis_test`. Penerapan ke `hitung_gamis` (`migrate` lalu `db:seed`, bukan `fresh`) hanya setelah pemilik mengonfirmasi |
| 4 | Model + relasi (bagian 7) | `tinker` bisa `Category::with('sizes')->get()` |
| 5 | Service: `PesananService`, `HargaService` (bagian 8) | Feature test hijau |
| 6 | Route, Controller, FormRequest (bagian 9) | `php artisan route:list` sesuai tabel |
| 7 | Pasang `gamis.css` (Lampiran A), layout, dan halaman kasir (bagian 10, Lampiran B.1 dan B.2) | Tampilan sama dengan desain Night City; total di layar sama dengan total di database |
| 8 | Halaman riwayat, laporan, admin harga, dan form CRUD (Lampiran B.3 sampai B.6) | Semua alur di bagian 12 lolos |
| 9 | (Opsional) login admin | Rute admin butuh login |

Aturan kerja:
- Fase 1 sampai 5 dikerjakan dan diverifikasi di `hitung_gamis_test`. Menerapkan migrasi dan seeder ke `hitung_gamis` baru dilakukan setelah konfirmasi eksplisit pemilik, dan memakai `php artisan migrate` (maju saja), **bukan** `migrate:fresh`.
- Berhenti dan minta konfirmasi sebelum tindakan di kategori "butuh konfirmasi" atau "dilarang" pada bagian 2.3.
- Setelah tiap fase, jalankan test (bila guard 11.1 sudah terpasang) dan laporkan hasilnya singkat, tanpa menampilkan password.
- Jangan mengubah nama tabel/kolom/procedure dari spesifikasi ini tanpa alasan tertulis.
- Jangan menghitung total di JavaScript lalu mempercayainya. **Total yang tersimpan selalu dihitung ulang oleh database.**
- Semua logika uang memakai integer rupiah (tanpa desimal).

---

## 4. Skema database

### 4.1 Diagram relasi

```
categories 1 ──< sizes 1 ──< order_items >── 1 orders
                   │
                   └──< price_logs   (diisi otomatis oleh trigger)
```

### 4.2 Tabel

**categories**
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| kode | varchar(20) unique | `anak`, `dewasa`, `luar` |
| nama | varchar(50) | "Jubah anak" |
| urutan | tinyint unsigned default 0 | urutan tab |
| timestamps | | |

**sizes**
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| category_id | FK categories | `restrictOnDelete` |
| nama | varchar(20) | "No 2", "M", "XL" |
| harga | int unsigned | rupiah |
| urutan | tinyint unsigned default 0 | |
| is_active | boolean default true | nonaktif = tidak tampil di kasir |
| timestamps | | |
| | unique(category_id, nama) | |

**orders**
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| kode_pesanan | varchar(40) unique | contoh `INV-20261020-00007` |
| nama_pembeli | varchar(100) null | |
| total_item | int unsigned default 0 | |
| total_harga | bigint unsigned default 0 | |
| status | enum('selesai','batal') default 'selesai' | |
| catatan | varchar(255) null | |
| dibatalkan_pada | timestamp null | |
| timestamps | | |
| | index(created_at), index(status) | |

**order_items**
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| order_id | FK orders | `cascadeOnDelete` |
| size_id | FK sizes | `restrictOnDelete` (ukuran yang sudah pernah terjual tidak boleh dihapus) |
| qty | int unsigned | > 0 |
| harga_satuan | int unsigned | **salinan harga saat transaksi**, bukan referensi ke `sizes.harga` |
| subtotal | bigint unsigned | hasil `fn_subtotal` |
| | unique(order_id, size_id) | |

**price_logs**
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| size_id | FK sizes | `cascadeOnDelete` |
| harga_lama | int unsigned | |
| harga_baru | int unsigned | |
| diubah_pada | timestamp default current_timestamp | |

### 4.3 Migrasi tabel (fase 1)

Semua migration adalah **file baru**. Jangan mengubah atau menghapus migration yang sudah ada di project. Bila nama tabel bentrok dengan tabel yang sudah ada, berhenti dan lapor (bagian 2.2).

Buat 5 file migrasi dengan urutan: categories, sizes, orders, order_items, price_logs. Contoh yang paling penting:

```php
// create_sizes_table
Schema::create('sizes', function (Blueprint $t) {
    $t->id();
    $t->foreignId('category_id')->constrained()->restrictOnDelete();
    $t->string('nama', 20);
    $t->unsignedInteger('harga');
    $t->unsignedTinyInteger('urutan')->default(0);
    $t->boolean('is_active')->default(true);
    $t->timestamps();
    $t->unique(['category_id', 'nama']);
});

// create_order_items_table
Schema::create('order_items', function (Blueprint $t) {
    $t->id();
    $t->foreignId('order_id')->constrained()->cascadeOnDelete();
    $t->foreignId('size_id')->constrained()->restrictOnDelete();
    $t->unsignedInteger('qty');
    $t->unsignedInteger('harga_satuan');
    $t->unsignedBigInteger('subtotal');
    $t->unique(['order_id', 'size_id']);
});
```

Tabel lain mengikuti tabel di 4.2. `orders.status` memakai `$t->enum('status', ['selesai','batal'])->default('selesai')`.

---

## 5. Seeder (fase 3)

Buat `DatabaseSeeder` yang memanggil `CategorySeeder` dan `SizeSeeder`. Gunakan `updateOrCreate` agar bisa dijalankan berulang.

> **Peringatan database aplikasi:** `updateOrCreate` mengembalikan harga ke nilai awal. Di `hitung_gamis`, jalankan `db:seed` **hanya sekali**, saat tabel `sizes` masih kosong. Bila `sizes` sudah berisi (apalagi harganya sudah diubah pemilik), **jangan** seed ulang. Seeder tidak boleh menghapus data lain.

Kategori: `anak` (urutan 1), `dewasa` (2), `luar` (3).

Harga awal (diambil dari catatan tulisan tangan):

| Kategori | Ukuran: harga |
|---|---|
| anak | No 2: 36500, No 4: 39000, No 6: 39000, No 8: 41500, No 10: 44000, No 12: 44000, No 14: 46500 |
| dewasa | M: 51000, L: 54000, XL: 57000, XXL: 54000 |
| luar | M: 60000, L: 60000, XL: 60000 |

> **Catatan data yang belum pasti** (tulis juga sebagai komentar di seeder):
> 1. Harga XXL dewasa terbaca 54000 (sama dengan L). Kemungkinan seharusnya 60000. Pemilik harus mengonfirmasi.
> 2. Ukuran M jubah luar tampak dicoret di catatan. Tetap di-seed dengan `is_active = true`; pemilik bisa menonaktifkannya lewat halaman admin.

---

## 6. Function, Procedure, Trigger (fase 2)

Buat **satu file migrasi** `create_routines_and_triggers` yang menjalankan SQL di bawah dengan `DB::unprepared(...)`. Karena memakai PDO, **tidak perlu** perintah `DELIMITER`. Di method `down()`, jalankan `DROP ... IF EXISTS` untuk semuanya (urutan: trigger, procedure, function).

### 6.1 Function

```sql
-- Hitung subtotal satu baris
CREATE FUNCTION fn_subtotal(p_qty INT, p_harga INT)
RETURNS BIGINT
DETERMINISTIC
RETURN CAST(p_qty AS SIGNED) * CAST(p_harga AS SIGNED);

-- Total harga seluruh item dalam satu pesanan
CREATE FUNCTION fn_total_pesanan(p_order_id BIGINT)
RETURNS BIGINT
READS SQL DATA
RETURN (SELECT COALESCE(SUM(subtotal), 0) FROM order_items WHERE order_id = p_order_id);

-- Format kode pesanan: INV-YYYYMMDD-00007
CREATE FUNCTION fn_format_kode(p_id BIGINT, p_tanggal DATETIME)
RETURNS VARCHAR(40)
DETERMINISTIC
RETURN CONCAT('INV-', DATE_FORMAT(p_tanggal, '%Y%m%d'), '-', LPAD(p_id, 5, '0'));
```

### 6.2 Procedure: simpan pesanan (commit + rollback)

Menerima keranjang dalam bentuk JSON, contoh `[{"size_id":1,"qty":2},{"size_id":9,"qty":1}]`.

```sql
CREATE PROCEDURE sp_simpan_pesanan(
  IN  p_nama    VARCHAR(100),
  IN  p_catatan VARCHAR(255),
  IN  p_items   JSON,
  OUT p_order_id BIGINT
)
BEGIN
  DECLARE v_jumlah_json INT DEFAULT 0;
  DECLARE v_masuk INT DEFAULT 0;

  -- Jika ada error SQL apa pun: batalkan semua perubahan, lalu lempar ulang errornya
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  SET v_jumlah_json = COALESCE(JSON_LENGTH(p_items), 0);
  IF v_jumlah_json = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Keranjang kosong';
  END IF;

  START TRANSACTION;

  -- kode sementara (UUID) agar unique terpenuhi, diganti setelah id diketahui
  INSERT INTO orders (kode_pesanan, nama_pembeli, catatan, created_at, updated_at)
  VALUES (UUID(), NULLIF(TRIM(p_nama), ''), NULLIF(TRIM(p_catatan), ''), NOW(), NOW());
  SET p_order_id = LAST_INSERT_ID();

  -- Harga diambil dari tabel sizes (bukan dari klien), hanya ukuran aktif dan qty > 0
  INSERT INTO order_items (order_id, size_id, qty, harga_satuan, subtotal)
  SELECT p_order_id, s.id, j.qty, s.harga, fn_subtotal(j.qty, s.harga)
  FROM JSON_TABLE(p_items, '$[*]' COLUMNS (
         size_id BIGINT PATH '$.size_id',
         qty     INT    PATH '$.qty'
       )) AS j
  JOIN sizes s ON s.id = j.size_id AND s.is_active = 1
  WHERE j.qty > 0;

  SET v_masuk = ROW_COUNT();

  -- Jika ada baris yang ditolak (ukuran tidak aktif/tidak ada/qty <= 0), batalkan semuanya
  IF v_masuk <> v_jumlah_json THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ada item tidak valid (ukuran nonaktif/tidak ada atau qty <= 0)';
  END IF;

  UPDATE orders
  SET kode_pesanan = fn_format_kode(p_order_id, created_at),
      total_item   = (SELECT SUM(qty) FROM order_items WHERE order_id = p_order_id),
      total_harga  = fn_total_pesanan(p_order_id)
  WHERE id = p_order_id;

  COMMIT;
END;
```

> Karena `SIGNAL` dilempar di dalam blok transaksi, `EXIT HANDLER` akan menjalankan `ROLLBACK`. Jadi pesanan setengah jadi tidak pernah tersimpan.
> Duplikasi `size_id` dalam satu keranjang melanggar `unique(order_id, size_id)` dan ikut memicu rollback. Frontend harus menggabungkan qty per ukuran sebelum mengirim.

### 6.3 Procedure lain

```sql
-- Batalkan pesanan (status berubah, data tidak dihapus)
CREATE PROCEDURE sp_batalkan_pesanan(IN p_order_id BIGINT, IN p_alasan VARCHAR(255))
BEGIN
  DECLARE v_status VARCHAR(10);
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;

  START TRANSACTION;
  SELECT status INTO v_status FROM orders WHERE id = p_order_id FOR UPDATE;

  IF v_status IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pesanan tidak ditemukan';
  ELSEIF v_status = 'batal' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pesanan sudah dibatalkan';
  END IF;

  UPDATE orders
  SET status = 'batal', dibatalkan_pada = NOW(),
      catatan = CONCAT_WS(' | ', catatan, CONCAT('Batal: ', COALESCE(p_alasan, '-'))),
      updated_at = NOW()
  WHERE id = p_order_id;
  COMMIT;
END;

-- Rekap penjualan harian (hanya pesanan berstatus selesai)
CREATE PROCEDURE sp_rekap_harian(IN p_dari DATE, IN p_sampai DATE)
BEGIN
  SELECT DATE(created_at) AS tanggal,
         COUNT(*)         AS jumlah_pesanan,
         SUM(total_item)  AS jumlah_potong,
         SUM(total_harga) AS omzet
  FROM orders
  WHERE status = 'selesai' AND DATE(created_at) BETWEEN p_dari AND p_sampai
  GROUP BY DATE(created_at)
  ORDER BY tanggal DESC;
END;

-- Naik/turunkan harga satu kategori sekaligus (contoh +2000 atau -1500)
-- Dibatalkan total bila ada harga yang jadi negatif
CREATE PROCEDURE sp_ubah_harga_massal(IN p_category_id BIGINT, IN p_selisih INT)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;

  START TRANSACTION;
  UPDATE sizes
  SET harga = CAST(harga AS SIGNED) + p_selisih, updated_at = NOW()
  WHERE category_id = p_category_id;
  -- catatan: kolom harga UNSIGNED, hasil negatif akan memicu error SQL 1690 dan ROLLBACK otomatis

  IF ROW_COUNT() = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kategori tidak ditemukan atau tidak punya ukuran';
  END IF;
  COMMIT;
END;
```

> Agent: bila MySQL dalam mode non-strict sehingga nilai negatif tidak memicu error, tambahkan pengecekan eksplisit `IF EXISTS (SELECT 1 FROM sizes WHERE category_id = p_category_id AND CAST(harga AS SIGNED) < 0) THEN SIGNAL ...` setelah UPDATE.

### 6.4 Trigger (log perubahan harga)

```sql
CREATE TRIGGER trg_sizes_log_harga
AFTER UPDATE ON sizes
FOR EACH ROW
BEGIN
  IF OLD.harga <> NEW.harga THEN
    INSERT INTO price_logs (size_id, harga_lama, harga_baru, diubah_pada)
    VALUES (NEW.id, OLD.harga, NEW.harga, NOW());
  END IF;
END;
```

### 6.5 Query uji (hanya di database test)

Jalankan **hanya** pada `hitung_gamis_test` (misalnya `php artisan tinker --env=testing` atau klien MySQL yang diarahkan ke database itu). Query ini menambah data uji, jadi jangan dijalankan di `hitung_gamis`.

```sql
SELECT fn_subtotal(3, 39000);                  -- 117000
CALL sp_simpan_pesanan('Uji', NULL, '[{"size_id":1,"qty":2},{"size_id":9,"qty":1}]', @oid);
SELECT @oid;
SELECT * FROM orders WHERE id = @oid;           -- total_harga = 2*36500 + 1*51000 = 124000
CALL sp_simpan_pesanan('Gagal', NULL, '[{"size_id":1,"qty":2},{"size_id":9999,"qty":1}]', @oid2);
-- harus error, dan tidak ada baris baru di orders (bukti rollback)
```

---

## 7. Model dan relasi (fase 4)

```php
// Category
public function sizes() { return $this->hasMany(Size::class)->orderBy('urutan'); }

// Size
public function category() { return $this->belongsTo(Category::class); }
public function orderItems() { return $this->hasMany(OrderItem::class); }
public function priceLogs() { return $this->hasMany(PriceLog::class); }
public function scopeAktif($q) { return $q->where('is_active', true); }

// Order
public function items() { return $this->hasMany(OrderItem::class); }
// OrderItem: belongsTo(Order), belongsTo(Size)
```

Tambahkan accessor `harga_rupiah` pada `Size`, dan `total_rupiah` pada `Order`, yang memakai `number_format($v, 0, ',', '.')` dengan awalan `Rp `.
Kolom `status` di `Order` boleh dibuat PHP enum `StatusPesanan` (`selesai`, `batal`).

---

## 8. Service (fase 5)

### 8.1 PesananService (memanggil procedure)

```php
class PesananService
{
    /** @param array<int,array{size_id:int,qty:int}> $items */
    public function simpan(?string $nama, ?string $catatan, array $items): Order
    {
        // gabungkan size_id yang kembar sebelum dikirim
        $gabung = collect($items)->groupBy('size_id')
            ->map(fn ($g, $id) => ['size_id' => (int) $id, 'qty' => (int) $g->sum('qty')])
            ->values()->all();

        try {
            DB::statement('CALL sp_simpan_pesanan(?, ?, ?, @order_id)', [
                $nama, $catatan, json_encode($gabung),
            ]);
        } catch (QueryException $e) {
            // pesan dari SIGNAL ada di $e->errorInfo[2]
            throw new PesananGagalException($e->errorInfo[2] ?? 'Gagal menyimpan pesanan', 0, $e);
        }

        $id = DB::selectOne('SELECT @order_id AS id')->id;
        return Order::with('items.size.category')->findOrFail($id);
    }

    public function batalkan(Order $order, ?string $alasan): void
    {
        DB::statement('CALL sp_batalkan_pesanan(?, ?)', [$order->id, $alasan]);
    }

    public function rekap(string $dari, string $sampai): array
    {
        return DB::select('CALL sp_rekap_harian(?, ?)', [$dari, $sampai]);
    }
}
```

> Penting: `@order_id` adalah variabel sesi MySQL. Dua statement di atas harus berjalan di koneksi yang sama (default Laravel sudah begitu). Jangan membungkus pemanggilan procedure ini dengan `DB::transaction()` dari Laravel, karena procedure sudah punya `START TRANSACTION`/`COMMIT` sendiri dan transaksi bersarang akan ter-commit lebih awal.

### 8.2 HargaService (transaksi di sisi Laravel: commit + rollback manual)

Dipakai oleh halaman admin "Ubah harga semua ukuran" yang mengirim banyak perubahan sekaligus. Semuanya berhasil, atau tidak ada yang berubah.

```php
class HargaService
{
    /** @param array<int,array{harga:int,is_active:bool}> $perubahan [size_id => ['harga' => ..., 'is_active' => ...]] */
    public function ubahBanyak(array $perubahan): int
    {
        DB::beginTransaction();
        try {
            $berubah = 0;
            foreach ($perubahan as $sizeId => $data) {
                if ($data['harga'] < 0) {
                    throw new InvalidArgumentException("Harga ukuran #$sizeId tidak boleh negatif");
                }
                $berubah += Size::whereKey($sizeId)->update([
                    'harga'     => $data['harga'],
                    'is_active' => $data['is_active'],
                ]);
            }
            DB::commit();
            return $berubah;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function ubahMassal(Category $kategori, int $selisih): void
    {
        DB::statement('CALL sp_ubah_harga_massal(?, ?)', [$kategori->id, $selisih]);
    }
}
```

Trigger `trg_sizes_log_harga` otomatis mencatat tiap perubahan ke `price_logs`, jadi service tidak perlu menulis log sendiri.

---

## 9. Route, Controller, Validasi (fase 6)

| Method | URL | Controller@aksi | Fungsi |
|---|---|---|---|
| GET | `/` | `KasirController@index` | halaman kalkulator |
| POST | `/pesanan` | `PesananController@store` | simpan via `sp_simpan_pesanan` (menerima JSON) |
| GET | `/pesanan` | `PesananController@index` | riwayat, filter tanggal + status, paginasi 15 |
| GET | `/pesanan/{order}` | `PesananController@show` | detail |
| POST | `/pesanan/{order}/batal` | `PesananController@batal` | via `sp_batalkan_pesanan` |
| GET | `/laporan` | `LaporanController@index` | rekap via `sp_rekap_harian` |
| resource | `/admin/kategori` | `CategoryController` | CRUD kategori |
| resource | `/admin/ukuran` | `SizeController` | CRUD ukuran (termasuk toggle `is_active`) |
| GET/PUT | `/admin/harga` | `HargaController@edit/update` | edit harga **dan status aktif** semua ukuran sekaligus (`HargaService::ubahBanyak`) |
| POST | `/admin/harga/massal` | `HargaController@massal` | naik/turun harga satu kategori (`sp_ubah_harga_massal`) |
| GET | `/admin/ukuran/{size}/riwayat-harga` | `SizeController@riwayat` | isi `price_logs` |

FormRequest:
- `SimpanPesananRequest`: `items` required array min 1; `items.*.size_id` required exists:sizes,id; `items.*.qty` required integer min:1 max:999; `nama_pembeli` nullable string max:100; `catatan` nullable string max:255.
- `StoreSizeRequest` / `UpdateSizeRequest`: `category_id` exists; `nama` required max:20 dan unik per kategori (gunakan `Rule::unique('sizes')->where('category_id', ...)->ignore(...)`); `harga` integer min:0 max:100000000; `urutan` integer 0-255.
- `StoreCategoryRequest`: `kode` unik, huruf kecil tanpa spasi (`alpha_dash`); `nama` required.

Aturan penghapusan:
- Menghapus kategori yang masih punya ukuran: **ditolak**, tampilkan pesan "Hapus atau pindahkan ukurannya dulu".
- Menghapus ukuran yang pernah terjual (ada di `order_items`): **ditolak** (tangkap `QueryException` kode 23000), sarankan menonaktifkan ukuran lewat `is_active`.
- Pesanan **tidak pernah dihapus**, hanya dibatalkan.

`PesananController@store` mengembalikan JSON `{ "ok": true, "kode": "INV-...", "total": 124000 }` atau HTTP 422 `{ "ok": false, "pesan": "..." }` ketika `PesananGagalException` terjadi.

---

## 10. Antarmuka (fase 7 dan 8): tema Night City

Desain ini **sudah disetujui pemilik**. Agent tidak merancang ulang tampilan, tugasnya memasang desain yang sudah jadi. Kode CSS lengkap ada di **Lampiran A**, dan kerangka Blade acuan ada di **Lampiran B**.

### 10.1 Aturan utama

1. Simpan CSS Lampiran A **apa adanya** ke `public/css/gamis.css`. Jangan mengubah warna, font, ukuran, atau animasinya.
2. Pakai **nama class persis** seperti di Lampiran B. Kalau perlu elemen baru yang belum punya gaya, tambahkan class baru di **bagian paling bawah** `gamis.css` dengan memakai variabel yang sudah ada (`var(--card)`, `var(--red)`, dan seterusnya). Jangan membuat warna atau font baru.
3. Perubahan visual **tidak boleh** mengubah logic, route, controller, service, procedure, atau skema database.
4. Tema ini **hanya mode gelap**. Jangan menambah mode terang.
5. Banner `.note` ("Pratinjau tampilan...") hanya ada di pratinjau. **Jangan dirender** di aplikasi.

### 10.2 Gaya singkat (untuk pemahaman, sumber kebenaran tetap CSS)

| Elemen | Gaya |
|---|---|
| Latar | navy hampir hitam `#070b14`, grid tipis cyan 4% setiap 40px, semburat merah sangat samar di pojok kiri atas |
| Kartu/baris | biru keabu-abuan `#0f1829`, border tipis, sudut hampir siku (2px), garis aksen 3px di sisi kiri |
| Aksen utama | merah neon `#ff2a4d`: total harga, tombol simpan, penanda baris terpilih, garis margin kiri |
| Aksen kedua | cyan `#2fd8e6`: tab aktif, fokus input, status "selesai", hover tombol sekunder |
| Font | Chakra Petch (teks dan judul huruf kapital), Share Tech Mono (harga, jumlah, kode pesanan, tabel) |
| Gerak | transisi 0,15 sampai 0,2 detik pada hover dan tombol, toast muncul pelan; semua dimatikan bila pengguna memilih "kurangi gerakan" |
| Glow | hanya pada angka total dan tombol utama saat di-hover. Jangan menambah glow di tempat lain |

### 10.3 Layout

- Satu kolom, lebar maksimal 600px, rata tengah, mobile-first.
- Judul halaman `h1` tampil dengan awalan `// ` merah (dibuat oleh CSS, jangan menulisnya di HTML).
- Menu utama (Kasir, Riwayat, Laporan, Admin harga) memakai `<nav>` berisi `<a class="t">`; menu yang sedang aktif diberi `aria-pressed="true"`.
- Di halaman kasir, tab kategori memakai `<button class="t">` dengan `aria-pressed` dan jumlah potong di `<small>`.
- Bar bawah (`.bar`) menempel di dasar layar dan menghormati `env(safe-area-inset-bottom)` (sudah ada di CSS).

### 10.4 Halaman dan elemen

| Halaman | Elemen utama (nama class) |
|---|---|
| Kasir `/` | `nav > .t`, daftar `.row` (`.sz`, `.pr`, `.st` berisi `button`, `b`, `button.p`), `.bar` (`.sub`, `.tot`, `.bt`), `.toast` |
| Riwayat `/pesanan` | `.card > .h` (klik untuk membuka), `.det` untuk rincian, `.badge.ok` / `.badge.no`, tombol batal `.btn.g.s` dengan konfirmasi dua langkah |
| Laporan `/laporan` | `h2`, `.wrap > table`, baris terakhir berisi total (`th`) |
| Admin harga `/admin/harga` | `h2` per kategori, `.row` dengan `.pr input[type=number]` dan `label.sw` (checkbox aktif), `.bt` untuk ubah massal, `.btn` simpan semua, `.card.mut` untuk riwayat harga |
| Form CRUD kategori dan ukuran | `.card`, `label.fld > span + input/select`, `.err` untuk pesan validasi, `.aksi` berisi `.btn` (simpan) dan `.btn.g` (batal) |
| Pesan flash dari server | `.toast` (lihat Lampiran B.1) |

### 10.5 Perilaku kasir (Alpine.js)

Sama seperti pada bagian sebelumnya dan **tidak berubah** oleh tema:

1. Server mengirim kategori beserta ukuran **aktif** sebagai JSON ke komponen Alpine `kasir` (kode lengkap di Lampiran B.2).
2. State keranjang berbentuk `{ [size_id]: qty }`. Total di layar hanya untuk tampilan; yang tersimpan selalu dihitung database.
3. Tombol Simpan mengirim `fetch POST /pesanan` (header `X-CSRF-TOKEN` dan `Accept: application/json`) hanya berisi item dengan qty lebih dari 0.
4. Sukses: tampilkan toast kode dan total **dari respons server**, lalu kosongkan keranjang dan nama pembeli. Gagal: tampilkan pesan error, keranjang tetap utuh.
5. Tombol Simpan nonaktif saat keranjang kosong atau sedang mengirim.

### 10.6 Aksesibilitas (wajib dipertahankan)

- Tombol `+` dan `−` punya `aria-label` ("Tambah No 2", "Kurangi No 2"). Setiap `input` punya `aria-label` atau `label`.
- Area sentuh minimal 40px. Fokus keyboard terlihat (outline cyan sudah diatur di CSS).
- Kontras teks terhadap latar tidak boleh diturunkan. Teks redup memakai `var(--mute)`, bukan warna yang lebih gelap lagi.

### 10.7 Yang jangan dilakukan pada tampilan

- Jangan menambah efek glow, partikel, scanline, atau animasi berulang.
- Jangan mengganti font, atau menambah ikon dan gambar dekoratif.
- Jangan membuat tampilan menjadi dashboard dengan banyak panel. Pertahankan satu kolom yang sederhana.
- Jangan memakai framework CSS (Tailwind, Bootstrap) untuk halaman ini. Cukup `gamis.css`.

---

## 11. Testing (wajib; persiapan di fase 1, test dijalankan sejak fase 5)

### 11.0 Aturan

- Semua feature test yang membutuhkan database memakai **MySQL `hitung_gamis_test`**, terpisah dari `hitung_gamis`. SQLite dan `:memory:` tidak boleh dipakai.
- Test **tidak boleh** menyentuh `hitung_gamis` dalam kondisi apa pun.
- Pakai trait **`DatabaseTruncation`**, **bukan** `RefreshDatabase`. Alasannya: `sp_simpan_pesanan` dan procedure lain menjalankan `START TRANSACTION`, `COMMIT`, dan `ROLLBACK` sendiri. Di MySQL, `START TRANSACTION` meng-commit transaksi yang sedang terbuka, sehingga transaksi pembungkus milik `RefreshDatabase` rusak dan data bocor antar test. `DatabaseTruncation` mengosongkan tabel di antara test dan tidak bergantung pada transaksi pembungkus.
- Karena tabel dikosongkan tiap test, test yang butuh kategori dan ukuran memanggil seeder di `setUp()`.

### 11.1 Persiapan dan guard (sebelum migration atau seeder untuk testing dijalankan)

1. Buat `.env.testing` sesuai bagian 2.4.
2. Buka `phpunit.xml`, bagian `<php>`. Hapus atau komentari baris bawaan `DB_CONNECTION=sqlite` dan `DB_DATABASE=:memory:` bila ada, lalu pastikan ada baris berikut. Tunjukkan perubahan ini ke pemilik dan minta izin lebih dulu karena `phpunit.xml` adalah file bawaan project:

```xml
<env name="APP_ENV" value="testing" force="true"/>
<env name="DB_CONNECTION" value="mysql" force="true"/>
<env name="DB_DATABASE" value="hitung_gamis_test" force="true"/>
```

3. Pasang guard di `tests/TestCase.php`. Guard berjalan sebelum trait database menyentuh database, dan menghentikan test bila targetnya salah:

```php
protected function setUpTraits()
{
    $this->pastikanDatabaseTest();   // wajib dipanggil SEBELUM trait database bekerja
    return parent::setUpTraits();
}

protected function pastikanDatabaseTest(): void
{
    $koneksi = config('database.default');
    $nama    = config("database.connections.$koneksi.database");

    if ($koneksi !== 'mysql' || $nama !== 'hitung_gamis_test') {
        throw new \RuntimeException(
            "DIHENTIKAN: test mengarah ke koneksi '$koneksi', database '$nama'. Seharusnya mysql / hitung_gamis_test."
        );
    }
}
```

4. Verifikasi target **sebelum** menjalankan migration atau seeder untuk testing:

```powershell
php artisan db:show --env=testing     # nama database yang tampil HARUS hitung_gamis_test
```

Bila bukan `hitung_gamis_test`, **berhenti** dan lapor ke pemilik.

### 11.2 Perintah testing

```powershell
php artisan db:show --env=testing                  # 1. cek target dulu
php artisan migrate:fresh --seed --env=testing     # 2. hanya untuk database test
php artisan test                                   # 3. jalankan test
```

- **Selalu** sertakan `--env=testing` pada perintah migrasi atau seeder untuk testing. `migrate:fresh --seed` **tanpa** `--env=testing` akan mengarah ke `hitung_gamis` dan **dilarang** (bagian 2.3).
- Ulangi `db:show --env=testing` bila `.env`, `.env.testing`, atau `phpunit.xml` berubah.

### 11.3 Daftar feature test minimal

1. `fn_subtotal` dan `fn_total_pesanan` mengembalikan nilai benar.
2. Simpan pesanan valid: baris `orders` dan `order_items` terbentuk, `total_harga` sesuai hitungan manual, `kode_pesanan` berformat `INV-YYYYMMDD-NNNNN`.
3. **Rollback**: keranjang berisi satu ukuran valid dan satu `size_id` yang tidak ada: pesanan ditolak, `orders` dan `order_items` tetap kosong.
4. Ukuran nonaktif ditolak (rollback).
5. Harga di `order_items.harga_satuan` tidak ikut berubah ketika harga di `sizes` diubah setelahnya.
6. Batalkan pesanan: status jadi `batal`; membatalkan dua kali menghasilkan error.
7. `HargaService::ubahBanyak` dengan salah satu harga negatif: **tidak ada** harga yang berubah (rollback manual).
8. `sp_ubah_harga_massal` menaikkan semua harga kategori, dan `price_logs` terisi sebanyak ukuran di kategori itu.
9. Hapus ukuran yang pernah terjual ditolak; hapus ukuran yang belum pernah terjual berhasil.
10. `sp_rekap_harian` tidak menghitung pesanan berstatus `batal`.
11. **Guard database**: nama database pada koneksi test adalah `hitung_gamis_test` dan koneksinya `mysql`.
12. **Relasi tabel**: `Category->sizes`, `Order->items->size->category`, dan `Size->priceLogs` mengembalikan data yang benar; foreign key `restrictOnDelete` dan `cascadeOnDelete` bekerja sesuai bagian 4.

---

## 12. Kriteria selesai

- [ ] Laporan Fase 0 sudah dikirim dan disetujui pemilik sebelum ada perubahan; laporan tidak memuat password.
- [ ] Database aplikasi `hitung_gamis` dan database test `hitung_gamis_test` ada, terpisah, dan keduanya MySQL 8.0+. Tidak ada SQLite atau file database yang dipakai.
- [ ] `.env` yang sudah berfungsi tidak ditimpa; `.env.testing` dan `phpunit.xml` mengarah ke `hitung_gamis_test`.
- [ ] `php artisan migrate:fresh --seed --env=testing` berjalan bersih pada `hitung_gamis_test` (target sudah diverifikasi dengan `db:show --env=testing`).
- [ ] Pada `hitung_gamis`, hanya `php artisan migrate` dan `php artisan db:seed` (sekali, tabel `sizes` kosong) yang dijalankan, setelah konfirmasi pemilik. Tidak ada `migrate:fresh`, `db:wipe`, atau perintah destruktif lain yang dijalankan di sana.
- [ ] Tidak ada tabel, data, migration, atau fitur lama yang dihapus atau diubah.
- [ ] Ada 3 kategori dan 14 ukuran dengan harga sesuai bagian 5.
- [ ] Semua test di bagian 11 hijau.
- [ ] Menyimpan pesanan dari kasir menghasilkan total di layar yang sama dengan `orders.total_harga`.
- [ ] Skenario rollback terbukti (test 3 dan 7).
- [ ] CRUD kategori dan ukuran berfungsi, termasuk aturan penolakan hapus.
- [ ] Riwayat harga muncul setelah harga diubah (bukti trigger).
- [ ] Laporan harian menampilkan omzet yang cocok dengan penjumlahan manual.
- [ ] `public/css/gamis.css` identik dengan Lampiran A (hanya boleh ada tambahan di bagian paling bawah).
- [ ] Semua halaman (kasir, riwayat, laporan, admin harga, form CRUD) memakai tema Night City dan nama class dari Lampiran B.
- [ ] Tidak ada perubahan pada route, controller, service, procedure, atau skema database yang disebabkan oleh pekerjaan tampilan.
- [ ] Tampilan nyaman di layar ponsel 360px: tidak ada scroll horizontal pada body, tombol mudah ditekan.
- [ ] README singkat: cara membuat kedua database, cara setup `.env` dan `.env.testing` (tanpa password), cara menjalankan test, dan daftar procedure/function/trigger beserta fungsinya.

---

## 13. Hal yang jangan dilakukan

- Jangan memakai Eloquent `Model::create` untuk menyimpan pesanan. Penyimpanan pesanan **harus** lewat `sp_simpan_pesanan`.
- Jangan mempercayai harga atau total dari klien.
- Jangan menyimpan harga hanya sebagai referensi di `order_items`; selalu salin ke `harga_satuan`.
- Jangan menghapus pesanan; gunakan pembatalan.
- Jangan membungkus pemanggilan procedure yang punya transaksi sendiri dengan `DB::transaction()`.
- Jangan memakai SQLite, `:memory:`, database berbasis file, atau server database lain.
- Jangan menjalankan `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `migrate:rollback`, `db:wipe`, atau perintah destruktif lain pada `hitung_gamis` atau database lain yang berpotensi berisi data penting tanpa konfirmasi eksplisit pemilik.
- Jangan menjalankan `migrate:fresh --seed` tanpa `--env=testing`.
- Jangan menampilkan atau menyalin password dari `.env` ke laporan, log, atau commit.
- Jangan menimpa `.env` yang sudah berfungsi, dan jangan menghapus tabel, data, migration, atau fitur yang sudah ada.
- Jangan mengubah nama class, warna, atau font pada `gamis.css` tanpa persetujuan pemilik.
- Jangan menambah Tailwind, Bootstrap, atau pustaka UI lain untuk halaman aplikasi ini.
- Jangan menampilkan banner `.note` dari pratinjau di aplikasi.

---

## Lampiran A: `public/css/gamis.css` (salin apa adanya)

```css
/* Hitung Gamis: tema Night City. Hanya visual. Nama class sama dengan pratinjau. */
:root{
  --bg:#070b14; --bg2:#0a1020; --card:#0f1829; --card2:#142036;
  --ink:#dbe6f5; --mute:#7a8ca8; --line:#1d2b44; --line2:#2b3f62;
  --red:#ff2a4d; --red-soft:rgba(255,42,77,.14);
  --cyan:#2fd8e6; --cyan-soft:rgba(47,216,230,.10);
  --sel:#12223a; --ok:#2fd8e6;
  --sans:"Chakra Petch",system-ui,sans-serif; --mono:"Share Tech Mono",ui-monospace,Consolas,monospace;
  --ease:cubic-bezier(.2,.7,.2,1);
  color-scheme:dark; box-sizing:border-box;
  padding-top:env(safe-area-inset-top,0px); padding-bottom:env(safe-area-inset-bottom,0px);
}
*{box-sizing:border-box}
body{
  margin:0; min-height:100vh; color:var(--ink); font-family:var(--sans); font-size:16px;
  background-color:var(--bg);
  background-image:
    radial-gradient(900px 420px at 0% 0%, rgba(255,42,77,.07), transparent 60%),
    linear-gradient(rgba(47,216,230,.035) 1px, transparent 1px),
    linear-gradient(90deg, rgba(47,216,230,.035) 1px, transparent 1px);
  background-size:auto, 40px 40px, 40px 40px;
}
main{max-width:600px; margin:0 auto; padding:14px 16px 170px; border-left:1px solid var(--red); margin-left:max(10px,calc(50% - 300px)); box-shadow:-1px 0 12px -4px rgba(255,42,77,.35)}
.note{font-family:var(--mono); font-size:.72rem; color:var(--mute); background:var(--bg2); border:1px solid var(--line); border-left:2px solid var(--cyan); border-radius:2px; padding:7px 10px; margin-bottom:12px}
h1{font-size:1.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.14em; margin:0 0 12px; line-height:1}
h1::before{content:"// "; color:var(--red)}
h2{font-size:.78rem; font-weight:600; text-transform:uppercase; letter-spacing:.16em; color:var(--mute); margin:18px 0 8px; padding-bottom:6px; border-bottom:1px solid var(--line)}
nav{display:flex; gap:6px; overflow-x:auto; margin-bottom:12px; padding-bottom:4px; scrollbar-width:thin; scrollbar-color:var(--line2) transparent}

/* tab */
.t{flex:none; padding:10px 14px; border:1px solid var(--line); background:var(--card); color:var(--mute); font:inherit; font-weight:600; font-size:.9rem; text-transform:uppercase; letter-spacing:.08em; border-radius:2px; cursor:pointer; white-space:nowrap; transition:color .18s var(--ease),border-color .18s var(--ease),background .18s var(--ease)}
.t:hover{color:var(--ink); border-color:var(--line2); background:var(--card2)}
.t[aria-pressed=true]{color:var(--cyan); border-color:var(--cyan); background:var(--cyan-soft); box-shadow:inset 0 -2px 0 var(--cyan)}
.t small{font-family:var(--mono); font-weight:400; margin-left:6px; opacity:.85}

/* baris ukuran */
.row{display:flex; align-items:center; gap:10px; background:var(--card); border:1px solid var(--line); border-left:3px solid var(--line2); border-radius:2px; padding:10px 12px; margin-bottom:8px; transition:border-color .18s var(--ease),background .18s var(--ease),transform .18s var(--ease)}
.row:hover{background:var(--card2); border-color:var(--line2)}
.row.on{background:var(--sel); border-color:var(--line2); border-left-color:var(--red)}
.sz{font-weight:700; font-size:1.1rem; min-width:56px; letter-spacing:.04em}
.pr{flex:1; color:var(--mute); font-family:var(--mono); font-size:.95rem}
.st{display:flex; align-items:center; gap:6px}
.st button{width:40px; height:40px; border-radius:2px; border:1px solid var(--line2); background:var(--bg2); color:var(--ink); font-size:1.3rem; cursor:pointer; line-height:1; transition:border-color .15s var(--ease),color .15s var(--ease),background .15s var(--ease),transform .12s var(--ease)}
.st button:hover{border-color:var(--cyan); color:var(--cyan)}
.st button:active{transform:scale(.94)}
.st .p{background:var(--red-soft); border-color:var(--red); color:#ffd3db}
.st .p:hover{background:var(--red); color:#10060a; border-color:var(--red)}
.st b{min-width:28px; text-align:center; font-family:var(--mono); font-size:1.1rem}

/* input */
input[type=text],input[type=number]{font:inherit; padding:9px 10px; border:1px solid var(--line2); border-radius:2px; background:var(--bg2); color:var(--ink); width:100%; transition:border-color .15s var(--ease),box-shadow .15s var(--ease)}
input::placeholder{color:#546681}
input:hover{border-color:#3a5380}
input:focus{outline:none; border-color:var(--cyan); box-shadow:0 0 0 2px var(--cyan-soft)}
.pr input{width:120px; padding:6px 8px; font-family:var(--mono)}
input[type=checkbox]{accent-color:var(--red); width:16px; height:16px}
button:focus-visible{outline:2px solid var(--cyan); outline-offset:2px}

/* tombol */
.btn{font:inherit; font-weight:700; font-size:.88rem; text-transform:uppercase; letter-spacing:.1em; padding:12px 16px; border-radius:2px; border:1px solid var(--red); background:var(--red-soft); color:#ffd3db; cursor:pointer; transition:background .18s var(--ease),color .18s var(--ease),box-shadow .18s var(--ease),border-color .18s var(--ease)}
.btn:hover{background:var(--red); color:#10060a; box-shadow:0 0 14px -2px rgba(255,42,77,.55)}
.btn.g{background:transparent; border-color:var(--line2); color:var(--ink)}
.btn.g:hover{border-color:var(--cyan); color:var(--cyan); background:var(--cyan-soft); box-shadow:none}
.btn[disabled]{opacity:.35; cursor:not-allowed; box-shadow:none}
.btn[disabled]:hover{background:var(--red-soft); color:#ffd3db}
.btn.s{padding:8px 12px; font-size:.78rem}

/* bar bawah */
.bar{position:fixed; left:0; right:0; bottom:0; background:rgba(8,13,24,.94); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); border-top:1px solid var(--red); box-shadow:0 -8px 24px -12px rgba(255,42,77,.35); padding:10px 16px calc(10px + env(safe-area-inset-bottom,0px))}
.bar>div{max-width:600px; margin:0 auto}
.sub{color:var(--mute); font-family:var(--mono); font-size:.76rem; display:flex; gap:4px 14px; flex-wrap:wrap; text-transform:uppercase}
.tot{display:flex; justify-content:space-between; align-items:center; margin:2px 0 8px}
.tot strong{font-family:var(--mono); font-weight:400; font-size:1.7rem; color:var(--red); text-shadow:0 0 10px rgba(255,42,77,.35)}
.bt{display:flex; gap:8px}.bt input{flex:1; min-width:0}

/* kartu, badge, tabel */
.badge{font-family:var(--mono); font-size:.68rem; text-transform:uppercase; letter-spacing:.1em; padding:2px 8px; border-radius:2px; border:1px solid currentColor}
.ok{color:var(--cyan)}.no{color:var(--red)}
.card{background:var(--card); border:1px solid var(--line); border-left:3px solid var(--line2); border-radius:2px; padding:10px 12px; margin-bottom:8px; transition:border-color .18s var(--ease),background .18s var(--ease)}
.card:hover{background:var(--card2); border-left-color:var(--cyan)}
.card .h{display:flex; justify-content:space-between; gap:8px; align-items:center; cursor:pointer}
.card b{font-family:var(--mono); font-weight:400; letter-spacing:.03em}
.mut{color:var(--mute); font-size:.85rem}
.det{margin-top:8px; border-top:1px dashed var(--line2); padding-top:8px; font-size:.9rem}
.det div{display:flex; justify-content:space-between; font-family:var(--mono)}
table{width:100%; border-collapse:collapse; background:var(--card); font-family:var(--mono); font-size:.88rem}
th{font-family:var(--sans); font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.12em; color:var(--mute); background:var(--bg2)}
td,th{padding:9px 8px; border-bottom:1px solid var(--line); text-align:right}
td:first-child,th:first-child{text-align:left}
tr:last-child th{color:var(--red)}
tr:hover td{background:var(--card2)}
.wrap{overflow-x:auto; border:1px solid var(--line); border-radius:2px}
.toast{position:fixed; top:calc(10px + env(safe-area-inset-top,0px)); left:50%; transform:translateX(-50%); background:var(--card2); color:var(--ink); border:1px solid var(--cyan); border-left:3px solid var(--red); padding:10px 16px; border-radius:2px; font-family:var(--mono); font-size:.85rem; z-index:5; max-width:90%; animation:in .22s var(--ease)}
@keyframes in{from{opacity:0; transform:translate(-50%,-6px)}to{opacity:1; transform:translate(-50%,0)}}
.sw{display:flex; align-items:center; gap:6px; font-size:.75rem; text-transform:uppercase; letter-spacing:.08em; color:var(--mute)}

@media (prefers-reduced-motion:reduce){*{transition:none!important; animation:none!important}}

/* tambahan untuk layout Blade dan form admin */
[x-cloak]{display:none!important}
a.t{text-decoration:none; display:inline-block}
select{font:inherit; padding:9px 10px; border:1px solid var(--line2); border-radius:2px; background:var(--bg2); color:var(--ink); width:100%}
select:focus{outline:none; border-color:var(--cyan); box-shadow:0 0 0 2px var(--cyan-soft)}
.fld{display:block; margin-bottom:12px}
.fld>span{display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:.12em; color:var(--mute); margin-bottom:4px}
.err{color:var(--red); font-family:var(--mono); font-size:.82rem; margin-top:4px}
.aksi{display:flex; gap:8px; flex-wrap:wrap; margin-top:12px}
a.btn{text-decoration:none; display:inline-block}
```

---

## Lampiran B: Kerangka Blade acuan

Kode di bawah adalah **acuan struktur dan nama class**. Sesuaikan nama route, variabel, dan model dengan yang sudah dibuat di fase sebelumnya, tetapi **jangan mengubah nama class**.

### B.1 Layout `resources/views/layouts/app.blade.php`

```blade
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('judul', 'Hitung Gamis') · Night City</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&family=Share+Tech+Mono&display=swap">
  <link rel="stylesheet" href="{{ asset('css/gamis.css') }}">
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
  @stack('head')
</head>
<body>
<main>
  <h1>Hitung Gamis</h1>
  <nav aria-label="Menu utama">
    <a class="t" href="{{ route('kasir') }}" @if(request()->routeIs('kasir')) aria-pressed="true" @endif>Kasir</a>
    <a class="t" href="{{ route('pesanan.index') }}" @if(request()->routeIs('pesanan.*')) aria-pressed="true" @endif>Riwayat</a>
    <a class="t" href="{{ route('laporan') }}" @if(request()->routeIs('laporan')) aria-pressed="true" @endif>Laporan</a>
    <a class="t" href="{{ route('admin.harga.edit') }}" @if(request()->routeIs('admin.*')) aria-pressed="true" @endif>Admin harga</a>
  </nav>

  @yield('isi')
</main>

@yield('bar') {{-- bar bawah khusus halaman kasir --}}

@if(session('pesan'))
  <div class="toast" x-data="{ s: true }" x-show="s" x-init="setTimeout(() => s = false, 2600)">{{ session('pesan') }}</div>
@endif
@if($errors->has('server'))
  <div class="toast" x-data="{ s: true }" x-show="s" x-init="setTimeout(() => s = false, 4000)">{{ $errors->first('server') }}</div>
@endif
</body>
</html>
```

Route yang dipakai di menu perlu diberi nama: `kasir`, `pesanan.index`, `pesanan.show`, `pesanan.batal`, `laporan`, `admin.harga.edit`, `admin.harga.update`, `admin.harga.massal`.

### B.2 Halaman kasir `resources/views/kasir.blade.php`

Controller mengirim `$kategori = Category::with(['sizes' => fn ($q) => $q->aktif()])->orderBy('urutan')->get(['id','nama'])`. Hanya field `id`, `nama`, dan pada ukuran `id`, `nama`, `harga` yang perlu sampai ke klien.

```blade
@extends('layouts.app')
@section('isi')
<div x-data="kasir(@js($kategori))" x-cloak>
  <nav aria-label="Kategori">
    <template x-for="c in kategori" :key="c.id">
      <button type="button" class="t" :aria-pressed="aktif === c.id" @click="aktif = c.id">
        <span x-text="c.nama"></span><small x-text="jumlahKategori(c.id)"></small>
      </button>
    </template>
  </nav>

  <template x-for="s in ukuranAktif" :key="s.id">
    <div class="row" :class="{ on: (qty[s.id] || 0) > 0 }">
      <div class="sz" x-text="s.nama"></div>
      <div class="pr" x-text="rupiah(s.harga)"></div>
      <div class="st">
        <button type="button" :aria-label="'Kurangi ' + s.nama" @click="kurang(s.id)">−</button>
        <b x-text="qty[s.id] || 0"></b>
        <button type="button" class="p" :aria-label="'Tambah ' + s.nama" @click="tambah(s.id)">+</button>
      </div>
    </div>
  </template>

  <div class="bar"><div>
    <div class="sub">
      <template x-for="c in kategoriTerpilih" :key="c.id">
        <span x-text="c.nama + ': ' + c.jumlah"></span>
      </template>
    </div>
    <div class="tot"><span class="mut" x-text="totalItem + ' potong'"></span><strong x-text="rupiah(totalHarga)"></strong></div>
    <div class="bt">
      <input type="text" x-model="nama" maxlength="100" placeholder="Nama pembeli (opsional)" aria-label="Nama pembeli">
      <button type="button" class="btn" :disabled="totalItem === 0 || mengirim" @click="simpan()">Simpan</button>
      <button type="button" class="btn g" @click="kosongkan()">Kosongkan</button>
    </div>
  </div></div>

  <div class="toast" x-show="pesan" x-text="pesan" x-cloak></div>
</div>
@endsection

@push('head')
<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('kasir', (kategori) => ({
    kategori, aktif: kategori.length ? kategori[0].id : null,
    qty: {}, nama: '', mengirim: false, pesan: '',
    ukuran(id) { return this.kategori.flatMap(c => c.sizes).find(s => s.id === id); },
    get ukuranAktif() { const c = this.kategori.find(k => k.id === this.aktif); return c ? c.sizes : []; },
    jumlahKategori(cid) { return this.kategori.find(c => c.id === cid).sizes.reduce((a, s) => a + (this.qty[s.id] || 0), 0); },
    get totalItem() { return Object.values(this.qty).reduce((a, b) => a + b, 0); },
    get totalHarga() { return Object.entries(this.qty).reduce((a, [id, q]) => a + q * this.ukuran(+id).harga, 0); },
    get kategoriTerpilih() {
      return this.kategori.map(c => ({ id: c.id, nama: c.nama, jumlah: this.jumlahKategori(c.id) })).filter(c => c.jumlah > 0);
    },
    tambah(id) { this.qty[id] = (this.qty[id] || 0) + 1; },
    kurang(id) { if ((this.qty[id] || 0) > 0) this.qty[id]--; },
    kosongkan() { this.qty = {}; },
    rupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); },
    tampil(m) { this.pesan = m; setTimeout(() => this.pesan = '', 2800); },
    async simpan() {
      if (this.totalItem === 0 || this.mengirim) return;
      this.mengirim = true;
      try {
        const r = await fetch('/pesanan', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json', 'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
          },
          body: JSON.stringify({
            nama_pembeli: this.nama || null,
            items: Object.entries(this.qty).filter(([, q]) => q > 0).map(([id, q]) => ({ size_id: +id, qty: q })),
          }),
        });
        const d = await r.json();
        if (r.ok && d.ok) {
          this.tampil('Tersimpan: ' + d.kode + ' · ' + this.rupiah(d.total));
          this.qty = {}; this.nama = '';
        } else {
          this.tampil(d.pesan || d.message || 'Gagal menyimpan pesanan');
        }
      } catch (e) {
        this.tampil('Koneksi bermasalah, coba lagi');
      } finally {
        this.mengirim = false;
      }
    },
  }));
});
</script>
@endpush
```

Catatan: `@push('head')` membuat `<script>` terpasang sebelum Alpine memulai (Alpine dimuat dengan `defer`), sehingga event `alpine:init` tertangkap.

### B.3 Riwayat `resources/views/pesanan/index.blade.php`

Tambahkan method `isSelesai(): bool` dan accessor `total_rupiah` pada model `Order`.

```blade
@extends('layouts.app')
@section('isi')
@forelse($orders as $o)
  <div class="card" x-data="{ buka: false, yakin: false }">
    <div class="h" @click="buka = !buka">
      <div>
        <b>{{ $o->kode_pesanan }}</b>
        <div class="mut">{{ $o->created_at->format('Y-m-d') }} · {{ $o->nama_pembeli ?: 'tanpa nama' }} · {{ $o->total_item }} potong</div>
      </div>
      <div style="text-align:right">
        <b>{{ $o->total_rupiah }}</b>
        <div><span class="badge {{ $o->isSelesai() ? 'ok' : 'no' }}">{{ $o->isSelesai() ? 'selesai' : 'batal' }}</span></div>
      </div>
    </div>
    <div class="det" x-show="buka" x-cloak>
      @foreach($o->items as $i)
        <div><span>{{ $i->size->category->nama }} {{ $i->size->nama }} × {{ $i->qty }}</span><span>Rp {{ number_format($i->subtotal, 0, ',', '.') }}</span></div>
      @endforeach
      @if($o->isSelesai())
        <form method="POST" action="{{ route('pesanan.batal', $o) }}" style="margin-top:8px">
          @csrf
          <button type="button" class="btn g s" x-show="!yakin" @click="yakin = true">Batalkan pesanan</button>
          <button type="submit" class="btn g s" x-show="yakin" x-cloak>Yakin batalkan?</button>
        </form>
      @endif
    </div>
  </div>
@empty
  <p class="mut">Belum ada pesanan.</p>
@endforelse
{{ $orders->links() }}
@endsection
```

Pagination bawaan Laravel tampil dengan gaya default. Buat view paginasi kustom `vendor/pagination/gamis.blade.php` yang memakai `<a class="t">` agar serasi, lalu panggil `{{ $orders->links('vendor.pagination.gamis') }}`.

### B.4 Laporan `resources/views/laporan.blade.php`

```blade
@extends('layouts.app')
@section('isi')
<h2>Rekap penjualan harian</h2>
<div class="wrap"><table>
  <tr><th>Tanggal</th><th>Pesanan</th><th>Potong</th><th>Omzet</th></tr>
  @foreach($rekap as $r)
    <tr><td>{{ $r->tanggal }}</td><td>{{ $r->jumlah_pesanan }}</td><td>{{ $r->jumlah_potong }}</td><td>Rp {{ number_format($r->omzet, 0, ',', '.') }}</td></tr>
  @endforeach
  <tr><th>Total</th><th>{{ collect($rekap)->sum('jumlah_pesanan') }}</th><th>{{ collect($rekap)->sum('jumlah_potong') }}</th><th>Rp {{ number_format(collect($rekap)->sum('omzet'), 0, ',', '.') }}</th></tr>
</table></div>
<p class="mut">Pesanan berstatus batal tidak dihitung.</p>
@endsection
```

### B.5 Admin harga `resources/views/admin/harga.blade.php`

Controller mengirim `$kategori = Category::with('sizes')->orderBy('urutan')->get()` (**termasuk** ukuran nonaktif) dan `$logs = PriceLog::with('size.category')->latest('diubah_pada')->limit(30)->get()`.

`HargaController@update` menyusun `[size_id => ['harga' => int, 'is_active' => bool]]` dari `harga[]` dan `aktif[]` (pakai `$request->boolean("aktif.$id")`), lalu memanggil `HargaService::ubahBanyak()`. Bila `ubahBanyak` melempar error, kembalikan `back()->withErrors(['server' => 'Dibatalkan (rollback): ...'])`. Bila sukses, `back()->with('pesan', "$n harga diubah")`.

```blade
@extends('layouts.app')
@section('isi')
<p class="mut">Ubah harga lalu tekan Simpan. Semua berubah atau tidak sama sekali.</p>

<form method="POST" action="{{ route('admin.harga.update') }}">
  @csrf @method('PUT')
  @foreach($kategori as $c)
    <h2>{{ $c->nama }}</h2>
    @foreach($c->sizes as $s)
      <div class="row">
        <div class="sz">{{ $s->nama }}</div>
        <div class="pr">
          <input type="number" min="0" inputmode="numeric" name="harga[{{ $s->id }}]"
                 value="{{ old('harga.'.$s->id, $s->harga) }}" aria-label="Harga {{ $s->nama }}">
        </div>
        <label class="sw">
          <input type="hidden" name="aktif[{{ $s->id }}]" value="0">
          <input type="checkbox" name="aktif[{{ $s->id }}]" value="1" @checked($s->is_active)> aktif
        </label>
      </div>
    @endforeach
  @endforeach
  <div class="aksi"><button class="btn">Simpan semua harga</button></div>
</form>

<h2>Ubah massal per kategori</h2>
@foreach($kategori as $c)
  <form method="POST" action="{{ route('admin.harga.massal') }}" class="bt" style="margin-bottom:8px">
    @csrf
    <input type="hidden" name="category_id" value="{{ $c->id }}">
    <input type="number" name="selisih" inputmode="numeric" placeholder="{{ $c->nama }}: selisih, contoh 2000 atau -1500" aria-label="Selisih harga {{ $c->nama }}">
    <button class="btn g s">Ubah massal</button>
  </form>
@endforeach

<h2>Riwayat perubahan harga</h2>
@forelse($logs as $l)
  <div class="card mut">{{ $l->size->category->nama }} {{ $l->size->nama }}: Rp {{ number_format($l->harga_lama, 0, ',', '.') }} → Rp {{ number_format($l->harga_baru, 0, ',', '.') }} · {{ \Illuminate\Support\Carbon::parse($l->diubah_pada)->format('Y-m-d H:i') }}</div>
@empty
  <p class="mut">Belum ada perubahan.</p>
@endforelse
@endsection
```

### B.6 Form CRUD kategori dan ukuran (pola)

```blade
<form method="POST" action="{{ route('admin.ukuran.store') }}" class="card">
  @csrf
  <label class="fld"><span>Kategori</span>
    <select name="category_id">@foreach($kategori as $c)<option value="{{ $c->id }}">{{ $c->nama }}</option>@endforeach</select>
    @error('category_id')<div class="err">{{ $message }}</div>@enderror
  </label>
  <label class="fld"><span>Nama ukuran</span><input type="text" name="nama" value="{{ old('nama') }}" maxlength="20">
    @error('nama')<div class="err">{{ $message }}</div>@enderror
  </label>
  <label class="fld"><span>Harga</span><input type="number" name="harga" min="0" inputmode="numeric" value="{{ old('harga') }}">
    @error('harga')<div class="err">{{ $message }}</div>@enderror
  </label>
  <div class="aksi"><button class="btn">Simpan</button><a class="btn g" href="{{ route('admin.ukuran.index') }}">Batal</a></div>
</form>
```

