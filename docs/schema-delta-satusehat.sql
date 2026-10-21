-- Delta: pemetaan terminologi & kebutuhan data SATUSEHAT (setelah schema dasar)
ALTER TABLE mtr_obat
  ADD COLUMN kode_kfa_virtual VARCHAR(30) NULL COMMENT 'kode produk obat virtual KFA (verifikasi pemakaian)' AFTER kode_kfa,
  ADD COLUMN nama_kfa VARCHAR(255) NULL AFTER kode_kfa_virtual,
  ADD COLUMN kode_bentuk_sediaan VARCHAR(30) NULL AFTER nama_kfa,
  ADD COLUMN nama_bentuk_sediaan VARCHAR(100) NULL AFTER kode_bentuk_sediaan;

ALTER TABLE mtr_pemeriksaan_lab
  ADD COLUMN kode_loinc VARCHAR(20) NULL AFTER nama,
  ADD COLUMN nama_loinc VARCHAR(255) NULL AFTER kode_loinc,
  ADD COLUMN kode_spesimen VARCHAR(30) NULL COMMENT 'SNOMED CT jenis spesimen' AFTER nama_loinc,
  ADD COLUMN nama_spesimen VARCHAR(100) NULL AFTER kode_spesimen;

ALTER TABLE mtr_parameter_lab
  ADD COLUMN kode_loinc VARCHAR(20) NULL AFTER nama_parameter,
  ADD COLUMN nama_loinc VARCHAR(255) NULL AFTER kode_loinc,
  ADD COLUMN satuan_ucum VARCHAR(30) NULL AFTER satuan,
  ADD COLUMN jenis_nilai ENUM('numerik','kualitatif') NOT NULL DEFAULT 'numerik' AFTER satuan_ucum;

ALTER TABLE mtr_poli
  ADD COLUMN kode_tipe_pelayanan VARCHAR(30) NULL COMMENT 'Encounter.serviceType (lampiran terminologi)' AFTER nama,
  ADD COLUMN nama_tipe_pelayanan VARCHAR(100) NULL AFTER kode_tipe_pelayanan;

CREATE TABLE mtr_nakes (
  id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama                 VARCHAR(150) NOT NULL,
  jenis                ENUM('perawat','apoteker','petugas_lab','lainnya') NOT NULL,
  nik                  VARCHAR(500) NULL COMMENT 'terenkripsi (cast encrypted)',
  nik_hash             CHAR(64) NULL COMMENT 'HMAC-SHA256 NIK',
  no_str               VARCHAR(50) NULL,
  ihs_practitioner_id  VARCHAR(64) NULL,
  pengguna_id          BIGINT UNSIGNED NULL COMMENT 'akun login (opsional)',
  aktif                TINYINT(1) NOT NULL DEFAULT 1,
  created_at           TIMESTAMP NULL DEFAULT NULL,
  updated_at           TIMESTAMP NULL DEFAULT NULL,
  deleted_at           TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_mtr_nakes_nik_hash (nik_hash),
  UNIQUE KEY uk_mtr_nakes_pengguna (pengguna_id),
  CONSTRAINT fk_mtr_nakes_pengguna FOREIGN KEY (pengguna_id) REFERENCES sys_pengguna (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mtr_lokasi (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode             VARCHAR(10)  NOT NULL,
  nama             VARCHAR(100) NOT NULL,
  ihs_location_id  VARCHAR(64)  NULL,
  aktif            TINYINT(1) NOT NULL DEFAULT 1,
  created_at       TIMESTAMP NULL DEFAULT NULL,
  updated_at       TIMESTAMP NULL DEFAULT NULL,
  deleted_at       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_mtr_lokasi_kode (kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE reg_kunjungan
  ADD COLUMN waktu_datang   TIMESTAMP NULL DEFAULT NULL AFTER tanggal_kunjungan,
  ADD COLUMN waktu_mulai    TIMESTAMP NULL DEFAULT NULL AFTER waktu_datang,
  ADD COLUMN waktu_selesai  TIMESTAMP NULL DEFAULT NULL AFTER waktu_mulai,
  ADD COLUMN kode_cara_keluar VARCHAR(30) NULL COMMENT 'kode terminologi cara keluar' AFTER status,
  ADD COLUMN nama_cara_keluar VARCHAR(100) NULL AFTER kode_cara_keluar;

CREATE TABLE reg_kunjungan_riwayat_status (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kunjungan_id  BIGINT UNSIGNED NOT NULL,
  status        VARCHAR(20) NOT NULL,
  waktu         TIMESTAMP NOT NULL,
  dibuat_oleh   BIGINT UNSIGNED NULL,
  created_at    TIMESTAMP NULL DEFAULT NULL,
  updated_at    TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_reg_riwayat_status (kunjungan_id, waktu),
  CONSTRAINT fk_reg_riwayat_kunjungan FOREIGN KEY (kunjungan_id) REFERENCES reg_kunjungan (id),
  CONSTRAINT fk_reg_riwayat_oleh      FOREIGN KEY (dibuat_oleh)  REFERENCES sys_pengguna (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE erm_resep_item
  ADD COLUMN dosis            DECIMAL(10,3) NULL AFTER aturan_pakai,
  ADD COLUMN satuan_dosis     VARCHAR(30) NULL AFTER dosis,
  ADD COLUMN frekuensi        SMALLINT UNSIGNED NULL COMMENT 'jumlah pemberian per periode' AFTER satuan_dosis,
  ADD COLUMN periode          SMALLINT UNSIGNED NULL COMMENT 'panjang periode' AFTER frekuensi,
  ADD COLUMN satuan_periode   ENUM('jam','hari','minggu') NULL AFTER periode,
  ADD COLUMN kode_rute        VARCHAR(30) NULL AFTER satuan_periode,
  ADD COLUMN nama_rute        VARCHAR(100) NULL AFTER kode_rute,
  ADD COLUMN lama_hari        SMALLINT UNSIGNED NULL AFTER nama_rute,
  ADD COLUMN aturan_tambahan  VARCHAR(255) NULL AFTER lama_hari;

CREATE TABLE lab_spesimen (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  permintaan_id      BIGINT UNSIGNED NOT NULL,
  kode_spesimen      VARCHAR(30)  NOT NULL COMMENT 'SNOMED CT',
  nama_spesimen      VARCHAR(100) NOT NULL,
  status             ENUM('menunggu_diambil','diambil','diterima','ditolak') NOT NULL DEFAULT 'menunggu_diambil',
  waktu_pengambilan  TIMESTAMP NULL DEFAULT NULL,
  diambil_oleh       BIGINT UNSIGNED NULL,
  catatan            VARCHAR(255) NULL,
  created_at         TIMESTAMP NULL DEFAULT NULL,
  updated_at         TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_lab_spesimen_permintaan (permintaan_id),
  CONSTRAINT fk_lab_spesimen_permintaan FOREIGN KEY (permintaan_id) REFERENCES lab_permintaan (id) ON DELETE CASCADE,
  CONSTRAINT fk_lab_spesimen_oleh       FOREIGN KEY (diambil_oleh)  REFERENCES sys_pengguna (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE lab_permintaan_detail
  ADD COLUMN spesimen_id BIGINT UNSIGNED NULL AFTER pemeriksaan_lab_id,
  ADD CONSTRAINT fk_lab_perm_det_spesimen FOREIGN KEY (spesimen_id) REFERENCES lab_spesimen (id);

ALTER TABLE sts_sinkronisasi
  ADD COLUMN sub_kunci VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'pembeda bila satu referensi menghasilkan >1 resource' AFTER referensi_id,
  ADD COLUMN aksi ENUM('POST','PUT') NOT NULL DEFAULT 'POST' AFTER jenis_resource,
  ADD COLUMN bergantung_pada_id BIGINT UNSIGNED NULL COMMENT 'baris sinkronisasi induk yang harus berhasil dulu' AFTER status,
  ADD UNIQUE KEY uk_sts_sinkronisasi_ref (jenis_resource, aksi, referensi_tabel, referensi_id, sub_kunci),
  ADD CONSTRAINT fk_sts_sinkronisasi_induk FOREIGN KEY (bergantung_pada_id) REFERENCES sts_sinkronisasi (id);
