<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Clean up existing routines to avoid conflicts
        DB::unprepared("DROP FUNCTION IF EXISTS fn_subtotal");
        DB::unprepared("DROP FUNCTION IF EXISTS fn_total_pesanan");
        DB::unprepared("DROP FUNCTION IF EXISTS fn_format_kode");
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_simpan_pesanan");
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_batalkan_pesanan");
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_rekap_harian");
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_ubah_harga_massal");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_sizes_log_harga");

        // 6.1 Function
        DB::unprepared("
            CREATE FUNCTION fn_subtotal(p_qty INT, p_harga INT)
            RETURNS BIGINT
            DETERMINISTIC
            RETURN CAST(p_qty AS SIGNED) * CAST(p_harga AS SIGNED);
        ");

        DB::unprepared("
            CREATE FUNCTION fn_total_pesanan(p_order_id BIGINT)
            RETURNS BIGINT
            READS SQL DATA
            RETURN (SELECT COALESCE(SUM(subtotal), 0) FROM order_items WHERE order_id = p_order_id);
        ");

        DB::unprepared("
            CREATE FUNCTION fn_format_kode(p_id BIGINT, p_tanggal DATETIME)
            RETURNS VARCHAR(40)
            DETERMINISTIC
            RETURN CONCAT('INV-', DATE_FORMAT(p_tanggal, '%Y%m%d'), '-', LPAD(p_id, 5, '0'));
        ");

        // 6.2 Procedure: Simpan Pesanan
        DB::unprepared("
            CREATE PROCEDURE sp_simpan_pesanan(
              IN  p_nama    VARCHAR(100),
              IN  p_catatan VARCHAR(255),
              IN  p_items   JSON,
              OUT p_order_id BIGINT
            )
            BEGIN
              DECLARE v_jumlah_json INT DEFAULT 0;
              DECLARE v_masuk INT DEFAULT 0;
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
              INSERT INTO orders (kode_pesanan, nama_pembeli, catatan, created_at, updated_at)
              VALUES (UUID(), NULLIF(TRIM(p_nama), ''), NULLIF(TRIM(p_catatan), ''), NOW(), NOW());
              SET p_order_id = LAST_INSERT_ID();

              INSERT INTO order_items (order_id, size_id, qty, harga_satuan, subtotal)
              SELECT p_order_id, s.id, j.qty, s.harga, fn_subtotal(j.qty, s.harga)
              FROM JSON_TABLE(p_items, '$[*]' COLUMNS (
                     size_id BIGINT PATH '$.size_id',
                     qty     INT    PATH '$.qty'
                   )) AS j
              JOIN sizes s ON s.id = j.size_id AND s.is_active = 1
              WHERE j.qty > 0;

              SET v_masuk = ROW_COUNT();
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
        ");

        // 6.3 Procedure lain
        DB::unprepared("
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
        ");

        DB::unprepared("
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
        ");

        DB::unprepared("
            CREATE PROCEDURE sp_ubah_harga_massal(IN p_category_id BIGINT, IN p_selisih INT)
            BEGIN
              DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
              START TRANSACTION;
              UPDATE sizes
              SET harga = CAST(harga AS SIGNED) + p_selisih, updated_at = NOW()
              WHERE category_id = p_category_id;
              
              IF EXISTS (SELECT 1 FROM sizes WHERE category_id = p_category_id AND CAST(harga AS SIGNED) < 0) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Harga tidak boleh menjadi negatif';
              END IF;

              IF ROW_COUNT() = 0 THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kategori tidak ditemukan atau tidak punya ukuran';
              END IF;
              COMMIT;
            END;
        ");

        // 6.4 Trigger
        DB::unprepared("
            CREATE TRIGGER trg_sizes_log_harga
            AFTER UPDATE ON sizes
            FOR EACH ROW
            BEGIN
              IF OLD.harga <> NEW.harga THEN
                INSERT INTO price_logs (size_id, harga_lama, harga_baru, diubah_pada)
                VALUES (NEW.id, OLD.harga, NEW.harga, NOW());
              END IF;
            END;
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS trg_sizes_log_harga");
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_ubah_harga_massal");
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_rekap_harian");
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_batalkan_pesanan");
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_simpan_pesanan");
        DB::unprepared("DROP FUNCTION IF EXISTS fn_format_kode");
        DB::unprepared("DROP FUNCTION IF EXISTS fn_total_pesanan");
        DB::unprepared("DROP FUNCTION IF EXISTS fn_subtotal");
    }
};
