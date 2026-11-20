-- =============================================================================
-- UPDATE 20 November 2026 - Koperasi Swamitra
-- Untuk deploy yang TIDAK memakai `php artisan migrate` (import/export database).
--
-- Cara pakai (sekali jalan):
--   mysql -u <user> -p <nama_database> < 20261120_update_hari_kerja_sabtu.sql
--
-- Aman dijalankan lebih dari sekali: setiap langkah memeriksa dulu kondisinya.
-- Butuh MySQL 8.0+ (memakai WITH RECURSIVE).
--
-- Setara dengan dua migrasi:
--   2026_09_13_000001_add_notes_and_system_fields_to_deposits_table
--   2026_11_20_000001_recalculate_workdays_saturday_as_workday
-- =============================================================================

-- =============================================================================
-- BAGIAN 1 - Kolom tambahan di tabel deposits
--   (migrasi 2026_09_13_000001)
--
-- notes               : keterangan transaksi, dipakai engine bunga untuk
--                       menulis "Bunga Simpanan Periode Oktober 2026".
-- is_system_generated : penanda baris buatan sistem, bukan input teller.
-- period              : periode (YYYY-MM) untuk transaksi bunga.
--
-- CATATAN: ALTER TABLE di MySQL melakukan commit implisit, jadi bagian ini
-- TIDAK bisa dibungkus transaksi. Tiap kolom hanya ditambahkan kalau belum ada,
-- jadi mengulang skrip ini tidak error.
-- =============================================================================

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'deposits' AND COLUMN_NAME = 'notes') = 0,
    'ALTER TABLE deposits ADD COLUMN notes TEXT NULL AFTER current_balance',
    'DO 0'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'deposits' AND COLUMN_NAME = 'is_system_generated') = 0,
    'ALTER TABLE deposits ADD COLUMN is_system_generated TINYINT(1) NOT NULL DEFAULT 0',
    'DO 0'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'deposits' AND COLUMN_NAME = 'period') = 0,
    'ALTER TABLE deposits ADD COLUMN period VARCHAR(20) NULL',
    'DO 0'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- =============================================================================
-- BAGIAN 2 - Hitung ulang pembagi hari kerja
--   (migrasi 2026_11_20_000001)
--
-- Sabtu kini dihitung sebagai hari kerja; hanya hari Minggu dan hari libur di
-- Manajemen Hari Libur yang tidak dihitung. Kolom
-- workday_year_counts.workday_count adalah PEMBAGI bunga harian:
--     bunga_harian = FLOOR(saldo x rate_percent / 100 / workday_count)
-- Kalau kode baru naik tapi angka ini tidak dihitung ulang, engine akan
-- mengakrual di hari Sabtu TETAPI masih membagi dengan jumlah hari kerja lama,
-- sehingga bunga setahun membengkak sekitar 20%.
--
-- Dihitung dari data tabel holidays yang sebenarnya, bukan angka hardcode, jadi
-- tetap benar kalau hari liburnya nanti ditambah/dikurangi.
-- Aturan: setiap tanggal dihitung hari kerja KECUALI hari Minggu dan hari libur
-- (type='holiday'); baris type='workday' (hari kerja pengganti) tetap dihitung
-- walau jatuh di hari Minggu.
-- =============================================================================

START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_hitung_hari_kerja;

CREATE TEMPORARY TABLE tmp_hitung_hari_kerja (
    year SMALLINT UNSIGNED PRIMARY KEY,
    jml  INT NOT NULL
);

INSERT INTO tmp_hitung_hari_kerja (year, jml)
WITH RECURSIVE tanggal AS (
    SELECT MAKEDATE(w.year, 1) AS dt, w.year AS year
    FROM (SELECT DISTINCT year FROM workday_year_counts) w
    UNION ALL
    SELECT dt + INTERVAL 1 DAY, year
    FROM tanggal
    WHERE YEAR(dt + INTERVAL 1 DAY) = year
)
SELECT t.year, COUNT(*)
FROM tanggal t
LEFT JOIN holidays h
       ON h.date = t.dt
      AND h.deleted_at IS NULL
WHERE COALESCE(h.type, IF(DAYOFWEEK(t.dt) = 1, 'holiday', 'workday')) = 'workday'
GROUP BY t.year;

UPDATE workday_year_counts w
  JOIN tmp_hitung_hari_kerja t ON t.year = w.year
   SET w.workday_count = t.jml,
       w.calculated_at = NOW(),
       w.updated_at    = NOW();

DROP TEMPORARY TABLE tmp_hitung_hari_kerja;

-- =============================================================================
-- BAGIAN 3 - Catat kedua migrasi supaya `php artisan migrate` tidak
--            menjalankannya ulang kalau suatu saat dipakai di server ini.
-- =============================================================================

SET @batch := (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) b);

INSERT INTO migrations (migration, batch)
SELECT '2026_09_13_000001_add_notes_and_system_fields_to_deposits_table', @batch
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT migration FROM migrations) m
     WHERE m.migration = '2026_09_13_000001_add_notes_and_system_fields_to_deposits_table'
);

INSERT INTO migrations (migration, batch)
SELECT '2026_11_20_000001_recalculate_workdays_saturday_as_workday', @batch
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT migration FROM migrations) m
     WHERE m.migration = '2026_11_20_000001_recalculate_workdays_saturday_as_workday'
);

COMMIT;

-- =============================================================================
-- BAGIAN 4 - Verifikasi
--   - deposits harus punya notes / is_system_generated / period
--   - 2026 harus jadi 312 (365 hari - 52 Minggu - 1 libur 17 Agustus)
--   - kedua migrasi harus tercatat
-- =============================================================================

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
  FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'deposits'
   AND COLUMN_NAME IN ('notes', 'is_system_generated', 'period')
 ORDER BY ORDINAL_POSITION;

SELECT year, workday_count, calculated_at FROM workday_year_counts ORDER BY year;

SELECT migration, batch FROM migrations
 WHERE migration IN (
    '2026_09_13_000001_add_notes_and_system_fields_to_deposits_table',
    '2026_11_20_000001_recalculate_workdays_saturday_as_workday'
 ) ORDER BY migration;
