-- ============================================================================
-- SIM KLINIK AVICENA v2.0 - DDL MySQL 8
-- Konvensi : prefix tabel per modul, nama tabel & kolom bahasa Indonesia
--            created_at, updated_at, deleted_at tetap (standar Laravel)
-- Prefix   : sys_ sistem | mtr_ master | reg_ pendaftaran | erm_ rekam medis
--            lab_ laboratorium | apt_ apotek | bil_ billing | sts_ SATUSEHAT
-- Catatan  : tabel bawaan Laravel (jobs, failed_jobs, migrations, cache,
--            sessions) dibuat lewat artisan, bukan di file ini.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. SYS_ : SISTEM
-- ============================================================================

CREATE TABLE sys_peran (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama         VARCHAR(50)  NOT NULL,
  keterangan   VARCHAR(255) NULL,
  created_at   TIMESTAMP NULL DEFAULT NULL,
  updated_at   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_sys_peran_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- mtr_poli & mtr_dokter dibuat lebih dulu karena dirujuk sys_pengguna
CREATE TABLE mtr_poli (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode             VARCHAR(5)   NOT NULL COMMENT 'INT, GIG, SRF, LAB',
  nama             VARCHAR(100) NOT NULL,
  ihs_location_id  VARCHAR(64)  NULL COMMENT 'ID Location SATUSEHAT',
  aktif            TINYINT(1)   NOT NULL DEFAULT 1,
  created_at       TIMESTAMP NULL DEFAULT NULL,
  updated_at       TIMESTAMP NULL DEFAULT NULL,
  deleted_at       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_mtr_poli_kode (kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mtr_dokter (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama                  VARCHAR(150) NOT NULL,
  spesialisasi          VARCHAR(100) NULL,
  poli_id               BIGINT UNSIGNED NULL,
  no_sip                VARCHAR(50)  NULL,
  nik                   VARCHAR(500) NULL COMMENT 'terenkripsi (cast encrypted)',
  ihs_practitioner_id   VARCHAR(64)  NULL,
  tarif_konsultasi      DECIMAL(15,2) NOT NULL DEFAULT 0,
  aktif                 TINYINT(1)   NOT NULL DEFAULT 1,
  created_at            TIMESTAMP NULL DEFAULT NULL,
  updated_at            TIMESTAMP NULL DEFAULT NULL,
  deleted_at            TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_mtr_dokter_poli (poli_id),
  CONSTRAINT fk_mtr_dokter_poli FOREIGN KEY (poli_id) REFERENCES mtr_poli (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sys_pengguna (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama             VARCHAR(150) NOT NULL,
  username         VARCHAR(50)  NOT NULL,
  email            VARCHAR(150) NULL,
  password         VARCHAR(255) NOT NULL COMMENT 'nama bawaan Laravel (hashed)',
  peran_id         BIGINT UNSIGNED NOT NULL,
  dokter_id        BIGINT UNSIGNED NULL COMMENT 'terisi jika pengguna adalah dokter',
  aktif            TINYINT(1) NOT NULL DEFAULT 1,
  login_terakhir   TIMESTAMP NULL DEFAULT NULL,
  remember_token   VARCHAR(100) NULL,
  created_at       TIMESTAMP NULL DEFAULT NULL,
  updated_at       TIMESTAMP NULL DEFAULT NULL,
  deleted_at       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_sys_pengguna_username (username),
  UNIQUE KEY uk_sys_pengguna_email (email),
  KEY idx_sys_pengguna_peran (peran_id),
  CONSTRAINT fk_sys_pengguna_peran  FOREIGN KEY (peran_id)  REFERENCES sys_peran (id),
  CONSTRAINT fk_sys_pengguna_dokter FOREIGN KEY (dokter_id) REFERENCES mtr_dokter (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sys_menu (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  induk_id    BIGINT UNSIGNED NULL COMMENT 'menu grup (induk); NULL untuk tingkat teratas',
  kode        VARCHAR(50)  NOT NULL COMMENT 'kontrak dengan kode aplikasi, mis. pendaftaran.pasien',
  nama        VARCHAR(100) NOT NULL,
  ikon        VARCHAR(50)  NULL,
  rute        VARCHAR(100) NULL COMMENT 'nama route; NULL untuk menu grup',
  urutan      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  aktif       TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NULL DEFAULT NULL,
  updated_at  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_sys_menu_kode (kode),
  CONSTRAINT fk_sys_menu_induk FOREIGN KEY (induk_id) REFERENCES sys_menu (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sys_hak_akses (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  peran_id      BIGINT UNSIGNED NOT NULL,
  menu_id       BIGINT UNSIGNED NOT NULL,
  bisa_lihat    TINYINT(1) NOT NULL DEFAULT 0,
  bisa_tambah   TINYINT(1) NOT NULL DEFAULT 0,
  bisa_ubah     TINYINT(1) NOT NULL DEFAULT 0,
  bisa_hapus    TINYINT(1) NOT NULL DEFAULT 0,
  created_at    TIMESTAMP NULL DEFAULT NULL,
  updated_at    TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_sys_hak_akses (peran_id, menu_id),
  CONSTRAINT fk_sys_hak_akses_peran FOREIGN KEY (peran_id) REFERENCES sys_peran (id),
  CONSTRAINT fk_sys_hak_akses_menu  FOREIGN KEY (menu_id)  REFERENCES sys_menu (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sys_log_aktivitas (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pengguna_id   BIGINT UNSIGNED NULL,
  aksi          VARCHAR(50)  NOT NULL COMMENT 'tambah, ubah, hapus, lihat, login, dll',
  nama_tabel    VARCHAR(100) NULL,
  data_id       BIGINT UNSIGNED NULL,
  nilai_lama    JSON NULL,
  nilai_baru    JSON NULL,
  alamat_ip     VARCHAR(45)  NULL,
  created_at    TIMESTAMP NULL DEFAULT NULL,
  updated_at    TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_sys_log_pengguna (pengguna_id),
  KEY idx_sys_log_tabel (nama_tabel, data_id),
  CONSTRAINT fk_sys_log_pengguna FOREIGN KEY (pengguna_id) REFERENCES sys_pengguna (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sys_pengaturan (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kunci       VARCHAR(100) NOT NULL,
  nilai       TEXT NULL,
  keterangan  VARCHAR(255) NULL,
  created_at  TIMESTAMP NULL DEFAULT NULL,
  updated_at  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_sys_pengaturan_kunci (kunci)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. MTR_ : DATA MASTER
-- ============================================================================

CREATE TABLE mtr_pasien (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  no_rekam_medis     VARCHAR(20)  NOT NULL,
  nik                VARCHAR(500) NOT NULL COMMENT 'terenkripsi (cast encrypted)',
  nik_hash           CHAR(64)     NOT NULL COMMENT 'HMAC-SHA256 NIK untuk pencarian',
  nama_lengkap       VARCHAR(150) NOT NULL,
  tempat_lahir       VARCHAR(100) NULL,
  tanggal_lahir      DATE NOT NULL,
  jenis_kelamin      ENUM('L','P') NOT NULL,
  golongan_darah     ENUM('A','B','AB','O','-') NOT NULL DEFAULT '-',
  alamat             TEXT NULL,
  no_telepon         VARCHAR(20)  NULL,
  nama_wali          VARCHAR(150) NULL,
  ihs_number         VARCHAR(64)  NULL COMMENT 'IHS Number dari SATUSEHAT',
  created_at         TIMESTAMP NULL DEFAULT NULL,
  updated_at         TIMESTAMP NULL DEFAULT NULL,
  deleted_at         TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_mtr_pasien_rm (no_rekam_medis),
  UNIQUE KEY uk_mtr_pasien_nik_hash (nik_hash),
  KEY idx_mtr_pasien_nama (nama_lengkap)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mtr_icd10 (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode              VARCHAR(10)  NOT NULL,
  nama_diagnosis    VARCHAR(255) NOT NULL,
  created_at        TIMESTAMP NULL DEFAULT NULL,
  updated_at        TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_mtr_icd10_kode (kode),
  KEY idx_mtr_icd10_nama (nama_diagnosis)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mtr_tindakan (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode        VARCHAR(20)  NOT NULL,
  nama        VARCHAR(150) NOT NULL,
  poli_id     BIGINT UNSIGNED NULL,
  tarif       DECIMAL(15,2) NOT NULL DEFAULT 0,
  aktif       TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NULL DEFAULT NULL,
  updated_at  TIMESTAMP NULL DEFAULT NULL,
  deleted_at  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_mtr_tindakan_kode (kode),
  CONSTRAINT fk_mtr_tindakan_poli FOREIGN KEY (poli_id) REFERENCES mtr_poli (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mtr_kategori_obat (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama        VARCHAR(100) NOT NULL,
  created_at  TIMESTAMP NULL DEFAULT NULL,
  updated_at  TIMESTAMP NULL DEFAULT NULL,
  deleted_at  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_mtr_kategori_obat_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mtr_satuan (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama        VARCHAR(50) NOT NULL,
  created_at  TIMESTAMP NULL DEFAULT NULL,
  updated_at  TIMESTAMP NULL DEFAULT NULL,
  deleted_at  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_mtr_satuan_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mtr_obat (
  id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode                VARCHAR(30)  NOT NULL,
  nama                VARCHAR(200) NOT NULL,
  kategori_obat_id    BIGINT UNSIGNED NULL,
  satuan_id           BIGINT UNSIGNED NOT NULL,
  golongan            ENUM('bebas','bebas_terbatas','keras','narkotika','psikotropika') NOT NULL DEFAULT 'bebas',
  stok_minimum        DECIMAL(12,2) NOT NULL DEFAULT 0,
  harga_jual          DECIMAL(15,2) NOT NULL DEFAULT 0,
  kode_kfa            VARCHAR(50)  NULL COMMENT 'kode KFA untuk SATUSEHAT (opsional)',
  aktif               TINYINT(1) NOT NULL DEFAULT 1,
  created_at          TIMESTAMP NULL DEFAULT NULL,
  updated_at          TIMESTAMP NULL DEFAULT NULL,
  deleted_at          TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_mtr_obat_kode (kode),
  KEY idx_mtr_obat_nama (nama),
  CONSTRAINT fk_mtr_obat_kategori FOREIGN KEY (kategori_obat_id) REFERENCES mtr_kategori_obat (id),
  CONSTRAINT fk_mtr_obat_satuan   FOREIGN KEY (satuan_id)        REFERENCES mtr_satuan (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mtr_supplier (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama           VARCHAR(150) NOT NULL,
  alamat         TEXT NULL,
  no_telepon     VARCHAR(20)  NULL,
  nama_kontak    VARCHAR(100) NULL,
  no_izin_pbf    VARCHAR(50)  NULL,
  aktif          TINYINT(1) NOT NULL DEFAULT 1,
  created_at     TIMESTAMP NULL DEFAULT NULL,
  updated_at     TIMESTAMP NULL DEFAULT NULL,
  deleted_at     TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mtr_pemeriksaan_lab (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode        VARCHAR(20)  NOT NULL,
  nama        VARCHAR(150) NOT NULL,
  tarif       DECIMAL(15,2) NOT NULL DEFAULT 0,
  aktif       TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NULL DEFAULT NULL,
  updated_at  TIMESTAMP NULL DEFAULT NULL,
  deleted_at  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_mtr_pemeriksaan_lab_kode (kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mtr_parameter_lab (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pemeriksaan_lab_id    BIGINT UNSIGNED NOT NULL,
  nama_parameter        VARCHAR(150) NOT NULL,
  satuan                VARCHAR(50)  NULL,
  nilai_rujukan_min     DECIMAL(12,3) NULL,
  nilai_rujukan_max     DECIMAL(12,3) NULL,
  keterangan_rujukan    VARCHAR(255) NULL,
  urutan                SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at            TIMESTAMP NULL DEFAULT NULL,
  updated_at            TIMESTAMP NULL DEFAULT NULL,
  deleted_at            TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_mtr_parameter_lab_pem (pemeriksaan_lab_id),
  CONSTRAINT fk_mtr_parameter_lab_pem FOREIGN KEY (pemeriksaan_lab_id) REFERENCES mtr_pemeriksaan_lab (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mtr_racikan (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama_menu    VARCHAR(150) NOT NULL,
  keterangan   VARCHAR(255) NULL,
  aktif        TINYINT(1) NOT NULL DEFAULT 1,
  created_at   TIMESTAMP NULL DEFAULT NULL,
  updated_at   TIMESTAMP NULL DEFAULT NULL,
  deleted_at   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mtr_racikan_bahan (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  racikan_id   BIGINT UNSIGNED NOT NULL,
  obat_id      BIGINT UNSIGNED NOT NULL,
  jumlah       DECIMAL(12,3) NOT NULL,
  satuan_id    BIGINT UNSIGNED NOT NULL,
  created_at   TIMESTAMP NULL DEFAULT NULL,
  updated_at   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_mtr_racikan_bahan (racikan_id, obat_id),
  CONSTRAINT fk_mtr_racikan_bahan_racikan FOREIGN KEY (racikan_id) REFERENCES mtr_racikan (id) ON DELETE CASCADE,
  CONSTRAINT fk_mtr_racikan_bahan_obat    FOREIGN KEY (obat_id)    REFERENCES mtr_obat (id),
  CONSTRAINT fk_mtr_racikan_bahan_satuan  FOREIGN KEY (satuan_id)  REFERENCES mtr_satuan (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mtr_metode_bayar (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama        VARCHAR(50) NOT NULL,
  aktif       TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NULL DEFAULT NULL,
  updated_at  TIMESTAMP NULL DEFAULT NULL,
  deleted_at  TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_mtr_metode_bayar_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. REG_ : PENDAFTARAN, TTV, ANTREAN
-- ============================================================================

CREATE TABLE reg_kunjungan (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  no_kunjungan       VARCHAR(30) NOT NULL,
  pasien_id          BIGINT UNSIGNED NOT NULL,
  tanggal_kunjungan  DATE NOT NULL,
  jenis_kunjungan    ENUM('poli','lab_mandiri','apotek_umum') NOT NULL DEFAULT 'poli',
  status             ENUM('terdaftar','ttv','menunggu','diperiksa','lab','farmasi','kasir','selesai','batal') NOT NULL DEFAULT 'terdaftar',
  ihs_encounter_id   VARCHAR(64) NULL,
  dibuat_oleh        BIGINT UNSIGNED NULL,
  created_at         TIMESTAMP NULL DEFAULT NULL,
  updated_at         TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_reg_kunjungan_no (no_kunjungan),
  KEY idx_reg_kunjungan_pasien (pasien_id),
  KEY idx_reg_kunjungan_tgl_status (tanggal_kunjungan, status),
  CONSTRAINT fk_reg_kunjungan_pasien FOREIGN KEY (pasien_id)   REFERENCES mtr_pasien (id),
  CONSTRAINT fk_reg_kunjungan_oleh   FOREIGN KEY (dibuat_oleh) REFERENCES sys_pengguna (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reg_ttv (
  id                        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kunjungan_id              BIGINT UNSIGNED NOT NULL,
  tekanan_darah_sistolik    SMALLINT UNSIGNED NULL,
  tekanan_darah_diastolik   SMALLINT UNSIGNED NULL,
  nadi                      SMALLINT UNSIGNED NULL COMMENT 'x/menit',
  suhu                      DECIMAL(4,1) NULL COMMENT 'derajat Celsius',
  berat_badan               DECIMAL(5,2) NULL COMMENT 'kg',
  tinggi_badan              DECIMAL(5,2) NULL COMMENT 'cm',
  saturasi_oksigen          TINYINT UNSIGNED NULL COMMENT 'persen',
  perawat_id                BIGINT UNSIGNED NULL,
  created_at                TIMESTAMP NULL DEFAULT NULL,
  updated_at                TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_reg_ttv_kunjungan (kunjungan_id),
  CONSTRAINT fk_reg_ttv_kunjungan FOREIGN KEY (kunjungan_id) REFERENCES reg_kunjungan (id),
  CONSTRAINT fk_reg_ttv_perawat   FOREIGN KEY (perawat_id)   REFERENCES sys_pengguna (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reg_penghitung_antrean (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  poli_id          BIGINT UNSIGNED NOT NULL,
  tanggal          DATE NOT NULL,
  nomor_terakhir   INT UNSIGNED NOT NULL DEFAULT 0,
  created_at       TIMESTAMP NULL DEFAULT NULL,
  updated_at       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_reg_penghitung (poli_id, tanggal),
  CONSTRAINT fk_reg_penghitung_poli FOREIGN KEY (poli_id) REFERENCES mtr_poli (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ambil dengan SELECT ... FOR UPDATE di dalam transaksi';

CREATE TABLE reg_antrean (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kunjungan_id     BIGINT UNSIGNED NOT NULL,
  poli_id          BIGINT UNSIGNED NOT NULL,
  tanggal          DATE NOT NULL,
  nomor_urut       INT UNSIGNED NOT NULL,
  nomor_antrean    VARCHAR(10) NOT NULL COMMENT 'contoh INT-001',
  status           ENUM('menunggu','dipanggil','dilayani','selesai','batal') NOT NULL DEFAULT 'menunggu',
  waktu_panggil    TIMESTAMP NULL DEFAULT NULL,
  waktu_selesai    TIMESTAMP NULL DEFAULT NULL,
  created_at       TIMESTAMP NULL DEFAULT NULL,
  updated_at       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_reg_antrean_urut (poli_id, tanggal, nomor_urut),
  KEY idx_reg_antrean_kunjungan (kunjungan_id),
  KEY idx_reg_antrean_status (poli_id, tanggal, status),
  CONSTRAINT fk_reg_antrean_kunjungan FOREIGN KEY (kunjungan_id) REFERENCES reg_kunjungan (id),
  CONSTRAINT fk_reg_antrean_poli      FOREIGN KEY (poli_id)      REFERENCES mtr_poli (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. ERM_ : REKAM MEDIS, E-RESEP, RUJUKAN LAB
-- ============================================================================

CREATE TABLE erm_pemeriksaan (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kunjungan_id   BIGINT UNSIGNED NOT NULL,
  antrean_id     BIGINT UNSIGNED NULL,
  poli_id        BIGINT UNSIGNED NOT NULL,
  dokter_id      BIGINT UNSIGNED NOT NULL,
  subjektif      TEXT NULL,
  objektif       TEXT NULL,
  asesmen        TEXT NULL,
  rencana        TEXT NULL,
  dikunci        TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'dikunci setelah kasir konfirmasi bayar',
  dikunci_pada   TIMESTAMP NULL DEFAULT NULL,
  created_at     TIMESTAMP NULL DEFAULT NULL,
  updated_at     TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_erm_pemeriksaan_kunjungan (kunjungan_id),
  KEY idx_erm_pemeriksaan_dokter (dokter_id),
  CONSTRAINT fk_erm_pemeriksaan_kunjungan FOREIGN KEY (kunjungan_id) REFERENCES reg_kunjungan (id),
  CONSTRAINT fk_erm_pemeriksaan_antrean   FOREIGN KEY (antrean_id)   REFERENCES reg_antrean (id),
  CONSTRAINT fk_erm_pemeriksaan_poli      FOREIGN KEY (poli_id)      REFERENCES mtr_poli (id),
  CONSTRAINT fk_erm_pemeriksaan_dokter    FOREIGN KEY (dokter_id)    REFERENCES mtr_dokter (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE erm_diagnosis (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pemeriksaan_id    BIGINT UNSIGNED NOT NULL,
  icd10_id          BIGINT UNSIGNED NOT NULL,
  jenis             ENUM('primer','sekunder') NOT NULL DEFAULT 'primer',
  ihs_condition_id  VARCHAR(64) NULL,
  created_at        TIMESTAMP NULL DEFAULT NULL,
  updated_at        TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_erm_diagnosis_pemeriksaan (pemeriksaan_id),
  CONSTRAINT fk_erm_diagnosis_pemeriksaan FOREIGN KEY (pemeriksaan_id) REFERENCES erm_pemeriksaan (id),
  CONSTRAINT fk_erm_diagnosis_icd10       FOREIGN KEY (icd10_id)       REFERENCES mtr_icd10 (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE erm_detail_penyakit_dalam (
  id                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pemeriksaan_id           BIGINT UNSIGNED NOT NULL,
  riwayat_penyakit_kronis  TEXT NULL,
  status_metabolik         TEXT NULL,
  review_sistem_organ      JSON NULL,
  created_at               TIMESTAMP NULL DEFAULT NULL,
  updated_at               TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_erm_detail_pd (pemeriksaan_id),
  CONSTRAINT fk_erm_detail_pd FOREIGN KEY (pemeriksaan_id) REFERENCES erm_pemeriksaan (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE erm_detail_saraf (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pemeriksaan_id     BIGINT UNSIGNED NOT NULL,
  evaluasi_motorik   JSON NULL,
  evaluasi_sensorik  JSON NULL,
  saraf_kranial      JSON NULL COMMENT 'N I - N XII',
  created_at         TIMESTAMP NULL DEFAULT NULL,
  updated_at         TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_erm_detail_saraf (pemeriksaan_id),
  CONSTRAINT fk_erm_detail_saraf FOREIGN KEY (pemeriksaan_id) REFERENCES erm_pemeriksaan (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE erm_odontogram (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pemeriksaan_id  BIGINT UNSIGNED NOT NULL,
  nomor_gigi      VARCHAR(3) NOT NULL COMMENT 'notasi FDI, mis. 11, 48, 55',
  permukaan       SET('mesial','distal','oklusal','bukal','lingual','palatal','seluruh') NULL,
  kondisi         VARCHAR(50) NOT NULL COMMENT 'karies, tambalan, hilang, mahkota, dll',
  tindakan        VARCHAR(100) NULL,
  catatan         VARCHAR(255) NULL,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_erm_odontogram_pem (pemeriksaan_id, nomor_gigi),
  CONSTRAINT fk_erm_odontogram_pem FOREIGN KEY (pemeriksaan_id) REFERENCES erm_pemeriksaan (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE erm_tindakan (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pemeriksaan_id  BIGINT UNSIGNED NOT NULL,
  tindakan_id     BIGINT UNSIGNED NOT NULL,
  jumlah          INT UNSIGNED NOT NULL DEFAULT 1,
  tarif           DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'snapshot tarif saat tindakan',
  catatan         VARCHAR(255) NULL,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_erm_tindakan_pem (pemeriksaan_id),
  CONSTRAINT fk_erm_tindakan_pem      FOREIGN KEY (pemeriksaan_id) REFERENCES erm_pemeriksaan (id),
  CONSTRAINT fk_erm_tindakan_tindakan FOREIGN KEY (tindakan_id)    REFERENCES mtr_tindakan (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE erm_resep (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pemeriksaan_id   BIGINT UNSIGNED NOT NULL,
  dokter_id        BIGINT UNSIGNED NOT NULL,
  no_resep         VARCHAR(30) NOT NULL,
  status           ENUM('diterbitkan','divalidasi','diracik','diserahkan','batal') NOT NULL DEFAULT 'diterbitkan',
  apoteker_id      BIGINT UNSIGNED NULL,
  divalidasi_pada  TIMESTAMP NULL DEFAULT NULL,
  diserahkan_pada  TIMESTAMP NULL DEFAULT NULL,
  catatan          VARCHAR(255) NULL,
  created_at       TIMESTAMP NULL DEFAULT NULL,
  updated_at       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_erm_resep_no (no_resep),
  KEY idx_erm_resep_pemeriksaan (pemeriksaan_id),
  KEY idx_erm_resep_status (status),
  CONSTRAINT fk_erm_resep_pemeriksaan FOREIGN KEY (pemeriksaan_id) REFERENCES erm_pemeriksaan (id),
  CONSTRAINT fk_erm_resep_dokter      FOREIGN KEY (dokter_id)      REFERENCES mtr_dokter (id),
  CONSTRAINT fk_erm_resep_apoteker    FOREIGN KEY (apoteker_id)    REFERENCES sys_pengguna (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE erm_resep_item (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  resep_id       BIGINT UNSIGNED NOT NULL,
  jenis          ENUM('reguler','racikan') NOT NULL DEFAULT 'reguler',
  obat_id        BIGINT UNSIGNED NULL COMMENT 'wajib jika jenis = reguler',
  racikan_id     BIGINT UNSIGNED NULL COMMENT 'template racikan (opsional)',
  nama_racikan   VARCHAR(150) NULL,
  jumlah         DECIMAL(12,2) NOT NULL COMMENT 'reguler: qty obat; racikan: jumlah bungkus/kapsul',
  aturan_pakai   VARCHAR(255) NULL,
  harga_satuan   DECIMAL(15,2) NOT NULL DEFAULT 0,
  biaya_jasa_racik DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'flat per resep racikan',
  catatan        VARCHAR(255) NULL,
  created_at     TIMESTAMP NULL DEFAULT NULL,
  updated_at     TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_erm_resep_item_resep (resep_id),
  CONSTRAINT fk_erm_resep_item_resep   FOREIGN KEY (resep_id)   REFERENCES erm_resep (id) ON DELETE CASCADE,
  CONSTRAINT fk_erm_resep_item_obat    FOREIGN KEY (obat_id)    REFERENCES mtr_obat (id),
  CONSTRAINT fk_erm_resep_item_racikan FOREIGN KEY (racikan_id) REFERENCES mtr_racikan (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE erm_resep_racikan_bahan (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  resep_item_id  BIGINT UNSIGNED NOT NULL,
  obat_id        BIGINT UNSIGNED NOT NULL,
  jumlah_bahan   DECIMAL(12,3) NOT NULL,
  satuan_id      BIGINT UNSIGNED NOT NULL,
  created_at     TIMESTAMP NULL DEFAULT NULL,
  updated_at     TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_erm_racikan_bahan_item (resep_item_id),
  CONSTRAINT fk_erm_racikan_bahan_item   FOREIGN KEY (resep_item_id) REFERENCES erm_resep_item (id) ON DELETE CASCADE,
  CONSTRAINT fk_erm_racikan_bahan_obat   FOREIGN KEY (obat_id)       REFERENCES mtr_obat (id),
  CONSTRAINT fk_erm_racikan_bahan_satuan FOREIGN KEY (satuan_id)     REFERENCES mtr_satuan (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE erm_rujukan_lab (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pemeriksaan_id  BIGINT UNSIGNED NOT NULL,
  dokter_id       BIGINT UNSIGNED NOT NULL,
  catatan_klinis  TEXT NULL,
  status          ENUM('diminta','diproses','selesai','batal') NOT NULL DEFAULT 'diminta',
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_erm_rujukan_lab_pem (pemeriksaan_id),
  CONSTRAINT fk_erm_rujukan_lab_pem    FOREIGN KEY (pemeriksaan_id) REFERENCES erm_pemeriksaan (id),
  CONSTRAINT fk_erm_rujukan_lab_dokter FOREIGN KEY (dokter_id)      REFERENCES mtr_dokter (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE erm_rujukan_lab_detail (
  id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rujukan_lab_id      BIGINT UNSIGNED NOT NULL,
  pemeriksaan_lab_id  BIGINT UNSIGNED NOT NULL,
  created_at          TIMESTAMP NULL DEFAULT NULL,
  updated_at          TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_erm_rujukan_lab_detail (rujukan_lab_id, pemeriksaan_lab_id),
  CONSTRAINT fk_erm_rujukan_det_rujukan FOREIGN KEY (rujukan_lab_id)     REFERENCES erm_rujukan_lab (id) ON DELETE CASCADE,
  CONSTRAINT fk_erm_rujukan_det_lab     FOREIGN KEY (pemeriksaan_lab_id) REFERENCES mtr_pemeriksaan_lab (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. LAB_ : LABORATORIUM
-- ============================================================================

CREATE TABLE lab_permintaan (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  no_permintaan   VARCHAR(30) NOT NULL,
  kunjungan_id    BIGINT UNSIGNED NOT NULL,
  antrean_id      BIGINT UNSIGNED NULL,
  sumber          ENUM('rujukan','mandiri') NOT NULL,
  rujukan_lab_id  BIGINT UNSIGNED NULL COMMENT 'terisi jika sumber = rujukan',
  dokter_id       BIGINT UNSIGNED NULL COMMENT 'dokter lab penanggung jawab, dipilih petugas lab',
  petugas_id      BIGINT UNSIGNED NULL,
  status          ENUM('menunggu','diproses','selesai','batal') NOT NULL DEFAULT 'menunggu',
  dikunci         TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'hasil dikunci lalu tagihan dikirim ke kasir',
  dikunci_pada    TIMESTAMP NULL DEFAULT NULL,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_lab_permintaan_no (no_permintaan),
  KEY idx_lab_permintaan_kunjungan (kunjungan_id),
  CONSTRAINT fk_lab_permintaan_kunjungan FOREIGN KEY (kunjungan_id)   REFERENCES reg_kunjungan (id),
  CONSTRAINT fk_lab_permintaan_antrean   FOREIGN KEY (antrean_id)     REFERENCES reg_antrean (id),
  CONSTRAINT fk_lab_permintaan_rujukan   FOREIGN KEY (rujukan_lab_id) REFERENCES erm_rujukan_lab (id),
  CONSTRAINT fk_lab_permintaan_dokter    FOREIGN KEY (dokter_id)      REFERENCES mtr_dokter (id),
  CONSTRAINT fk_lab_permintaan_petugas   FOREIGN KEY (petugas_id)     REFERENCES sys_pengguna (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lab_permintaan_detail (
  id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  permintaan_id       BIGINT UNSIGNED NOT NULL,
  pemeriksaan_lab_id  BIGINT UNSIGNED NOT NULL,
  tarif               DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'snapshot tarif',
  created_at          TIMESTAMP NULL DEFAULT NULL,
  updated_at          TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_lab_permintaan_detail (permintaan_id, pemeriksaan_lab_id),
  CONSTRAINT fk_lab_perm_det_perm FOREIGN KEY (permintaan_id)       REFERENCES lab_permintaan (id) ON DELETE CASCADE,
  CONSTRAINT fk_lab_perm_det_lab  FOREIGN KEY (pemeriksaan_lab_id)  REFERENCES mtr_pemeriksaan_lab (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lab_hasil (
  id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  permintaan_detail_id    BIGINT UNSIGNED NOT NULL,
  parameter_lab_id        BIGINT UNSIGNED NOT NULL,
  nilai                   VARCHAR(100) NOT NULL,
  satuan                  VARCHAR(50)  NULL,
  nilai_rujukan           VARCHAR(100) NULL COMMENT 'snapshot nilai rujukan',
  penanda                 ENUM('normal','rendah','tinggi','kritis') NULL,
  diinput_oleh            BIGINT UNSIGNED NULL,
  created_at              TIMESTAMP NULL DEFAULT NULL,
  updated_at              TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_lab_hasil (permintaan_detail_id, parameter_lab_id),
  CONSTRAINT fk_lab_hasil_detail    FOREIGN KEY (permintaan_detail_id) REFERENCES lab_permintaan_detail (id) ON DELETE CASCADE,
  CONSTRAINT fk_lab_hasil_parameter FOREIGN KEY (parameter_lab_id)     REFERENCES mtr_parameter_lab (id),
  CONSTRAINT fk_lab_hasil_petugas   FOREIGN KEY (diinput_oleh)         REFERENCES sys_pengguna (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. APT_ : APOTEK, FARMASI, INVENTARIS
-- ============================================================================

CREATE TABLE apt_penerimaan (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  no_faktur        VARCHAR(50) NOT NULL,
  supplier_id      BIGINT UNSIGNED NOT NULL,
  tanggal_faktur   DATE NOT NULL,
  tanggal_terima   DATE NOT NULL,
  total            DECIMAL(15,2) NOT NULL DEFAULT 0,
  catatan          VARCHAR(255) NULL,
  penerima_id      BIGINT UNSIGNED NULL,
  created_at       TIMESTAMP NULL DEFAULT NULL,
  updated_at       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_apt_penerimaan_faktur (supplier_id, no_faktur),
  CONSTRAINT fk_apt_penerimaan_supplier FOREIGN KEY (supplier_id) REFERENCES mtr_supplier (id),
  CONSTRAINT fk_apt_penerimaan_penerima FOREIGN KEY (penerima_id) REFERENCES sys_pengguna (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE apt_penerimaan_detail (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  penerimaan_id         BIGINT UNSIGNED NOT NULL,
  obat_id               BIGINT UNSIGNED NOT NULL,
  nomor_bets            VARCHAR(50) NOT NULL,
  tanggal_kedaluwarsa   DATE NOT NULL,
  jumlah                DECIMAL(12,2) NOT NULL,
  harga_beli            DECIMAL(15,2) NOT NULL,
  created_at            TIMESTAMP NULL DEFAULT NULL,
  updated_at            TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_apt_penerimaan_det_penerimaan (penerimaan_id),
  CONSTRAINT fk_apt_penerimaan_det_penerimaan FOREIGN KEY (penerimaan_id) REFERENCES apt_penerimaan (id) ON DELETE CASCADE,
  CONSTRAINT fk_apt_penerimaan_det_obat       FOREIGN KEY (obat_id)       REFERENCES mtr_obat (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE apt_stok_batch (
  id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  obat_id                 BIGINT UNSIGNED NOT NULL,
  penerimaan_detail_id    BIGINT UNSIGNED NULL,
  nomor_bets              VARCHAR(50) NOT NULL,
  tanggal_kedaluwarsa     DATE NOT NULL,
  harga_beli              DECIMAL(15,2) NOT NULL DEFAULT 0,
  jumlah_masuk            DECIMAL(12,2) NOT NULL,
  jumlah_sisa             DECIMAL(12,2) NOT NULL,
  created_at              TIMESTAMP NULL DEFAULT NULL,
  updated_at              TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_apt_stok_fefo (obat_id, tanggal_kedaluwarsa),
  CONSTRAINT fk_apt_stok_batch_obat      FOREIGN KEY (obat_id)              REFERENCES mtr_obat (id),
  CONSTRAINT fk_apt_stok_batch_penerimaan FOREIGN KEY (penerimaan_detail_id) REFERENCES apt_penerimaan_detail (id),
  CONSTRAINT ck_apt_stok_batch_sisa CHECK (jumlah_sisa >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE apt_mutasi_stok (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  stok_batch_id    BIGINT UNSIGNED NOT NULL,
  obat_id          BIGINT UNSIGNED NOT NULL,
  jenis_mutasi     ENUM('penerimaan','resep','racikan','penjualan_umum','retur_supplier','retur_pasien','opname_plus','opname_minus') NOT NULL,
  jumlah           DECIMAL(12,2) NOT NULL COMMENT 'positif = masuk, negatif = keluar',
  saldo_setelah    DECIMAL(12,2) NOT NULL COMMENT 'saldo batch setelah mutasi',
  referensi_tipe   VARCHAR(50) NULL,
  referensi_id     BIGINT UNSIGNED NULL,
  keterangan       VARCHAR(255) NULL,
  dibuat_oleh      BIGINT UNSIGNED NULL,
  created_at       TIMESTAMP NULL DEFAULT NULL,
  updated_at       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_apt_mutasi_obat (obat_id, created_at),
  KEY idx_apt_mutasi_batch (stok_batch_id),
  KEY idx_apt_mutasi_ref (referensi_tipe, referensi_id),
  CONSTRAINT fk_apt_mutasi_batch FOREIGN KEY (stok_batch_id) REFERENCES apt_stok_batch (id),
  CONSTRAINT fk_apt_mutasi_obat  FOREIGN KEY (obat_id)       REFERENCES mtr_obat (id),
  CONSTRAINT fk_apt_mutasi_oleh  FOREIGN KEY (dibuat_oleh)   REFERENCES sys_pengguna (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE apt_pengeluaran_resep (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  resep_item_id   BIGINT UNSIGNED NOT NULL,
  obat_id         BIGINT UNSIGNED NOT NULL,
  stok_batch_id   BIGINT UNSIGNED NOT NULL,
  jumlah          DECIMAL(12,3) NOT NULL,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_apt_pengeluaran_item (resep_item_id),
  CONSTRAINT fk_apt_pengeluaran_item  FOREIGN KEY (resep_item_id) REFERENCES erm_resep_item (id),
  CONSTRAINT fk_apt_pengeluaran_obat  FOREIGN KEY (obat_id)       REFERENCES mtr_obat (id),
  CONSTRAINT fk_apt_pengeluaran_batch FOREIGN KEY (stok_batch_id) REFERENCES apt_stok_batch (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE apt_penjualan_umum (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  no_penjualan   VARCHAR(30) NOT NULL,
  kunjungan_id   BIGINT UNSIGNED NULL,
  pasien_id      BIGINT UNSIGNED NULL,
  nama_pembeli   VARCHAR(150) NULL,
  tanggal        DATE NOT NULL,
  total          DECIMAL(15,2) NOT NULL DEFAULT 0,
  status         ENUM('draf','menunggu_bayar','lunas','batal') NOT NULL DEFAULT 'draf',
  petugas_id     BIGINT UNSIGNED NULL,
  created_at     TIMESTAMP NULL DEFAULT NULL,
  updated_at     TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_apt_penjualan_no (no_penjualan),
  CONSTRAINT fk_apt_penjualan_kunjungan FOREIGN KEY (kunjungan_id) REFERENCES reg_kunjungan (id),
  CONSTRAINT fk_apt_penjualan_pasien    FOREIGN KEY (pasien_id)    REFERENCES mtr_pasien (id),
  CONSTRAINT fk_apt_penjualan_petugas   FOREIGN KEY (petugas_id)   REFERENCES sys_pengguna (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE apt_penjualan_umum_detail (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  penjualan_id    BIGINT UNSIGNED NOT NULL,
  obat_id         BIGINT UNSIGNED NOT NULL,
  stok_batch_id   BIGINT UNSIGNED NOT NULL,
  jumlah          DECIMAL(12,2) NOT NULL,
  harga_satuan    DECIMAL(15,2) NOT NULL,
  subtotal        DECIMAL(15,2) NOT NULL,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_apt_penjualan_det (penjualan_id),
  CONSTRAINT fk_apt_penjualan_det_penjualan FOREIGN KEY (penjualan_id)  REFERENCES apt_penjualan_umum (id) ON DELETE CASCADE,
  CONSTRAINT fk_apt_penjualan_det_obat      FOREIGN KEY (obat_id)       REFERENCES mtr_obat (id),
  CONSTRAINT fk_apt_penjualan_det_batch     FOREIGN KEY (stok_batch_id) REFERENCES apt_stok_batch (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE apt_retur_supplier (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  no_retur        VARCHAR(30) NOT NULL,
  supplier_id     BIGINT UNSIGNED NOT NULL,
  penerimaan_id   BIGINT UNSIGNED NULL COMMENT 'faktur asal',
  alasan          ENUM('rusak','cacat','mendekati_kedaluwarsa','kedaluwarsa','lainnya') NOT NULL,
  tanggal         DATE NOT NULL,
  status          ENUM('draf','dikirim','diterima_supplier','batal') NOT NULL DEFAULT 'draf',
  catatan         VARCHAR(255) NULL,
  petugas_id      BIGINT UNSIGNED NULL,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_apt_retur_supplier_no (no_retur),
  CONSTRAINT fk_apt_retur_supplier_supplier   FOREIGN KEY (supplier_id)   REFERENCES mtr_supplier (id),
  CONSTRAINT fk_apt_retur_supplier_penerimaan FOREIGN KEY (penerimaan_id) REFERENCES apt_penerimaan (id),
  CONSTRAINT fk_apt_retur_supplier_petugas    FOREIGN KEY (petugas_id)    REFERENCES sys_pengguna (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE apt_retur_supplier_detail (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  retur_id        BIGINT UNSIGNED NOT NULL,
  stok_batch_id   BIGINT UNSIGNED NOT NULL,
  jumlah          DECIMAL(12,2) NOT NULL,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_apt_retur_sup_det_retur FOREIGN KEY (retur_id)      REFERENCES apt_retur_supplier (id) ON DELETE CASCADE,
  CONSTRAINT fk_apt_retur_sup_det_batch FOREIGN KEY (stok_batch_id) REFERENCES apt_stok_batch (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE apt_retur_pasien (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  no_retur         VARCHAR(30) NOT NULL,
  pasien_id        BIGINT UNSIGNED NULL,
  resep_id         BIGINT UNSIGNED NULL,
  penjualan_id     BIGINT UNSIGNED NULL,
  alasan           VARCHAR(255) NOT NULL,
  tanggal          DATE NOT NULL,
  total_pengembalian DECIMAL(15,2) NOT NULL DEFAULT 0,
  disetujui_oleh   BIGINT UNSIGNED NULL,
  created_at       TIMESTAMP NULL DEFAULT NULL,
  updated_at       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_apt_retur_pasien_no (no_retur),
  CONSTRAINT fk_apt_retur_pasien_pasien    FOREIGN KEY (pasien_id)      REFERENCES mtr_pasien (id),
  CONSTRAINT fk_apt_retur_pasien_resep     FOREIGN KEY (resep_id)       REFERENCES erm_resep (id),
  CONSTRAINT fk_apt_retur_pasien_penjualan FOREIGN KEY (penjualan_id)   REFERENCES apt_penjualan_umum (id),
  CONSTRAINT fk_apt_retur_pasien_setuju    FOREIGN KEY (disetujui_oleh) REFERENCES sys_pengguna (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE apt_retur_pasien_detail (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  retur_id        BIGINT UNSIGNED NOT NULL,
  obat_id         BIGINT UNSIGNED NOT NULL,
  stok_batch_id   BIGINT UNSIGNED NOT NULL,
  jumlah          DECIMAL(12,2) NOT NULL,
  nilai_kembali   DECIMAL(15,2) NOT NULL DEFAULT 0,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_apt_retur_pas_det_retur FOREIGN KEY (retur_id)      REFERENCES apt_retur_pasien (id) ON DELETE CASCADE,
  CONSTRAINT fk_apt_retur_pas_det_obat  FOREIGN KEY (obat_id)       REFERENCES mtr_obat (id),
  CONSTRAINT fk_apt_retur_pas_det_batch FOREIGN KEY (stok_batch_id) REFERENCES apt_stok_batch (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE apt_opname (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  no_opname    VARCHAR(30) NOT NULL,
  tanggal      DATE NOT NULL,
  apoteker_id  BIGINT UNSIGNED NULL,
  status       ENUM('draf','final') NOT NULL DEFAULT 'draf',
  catatan      VARCHAR(255) NULL,
  created_at   TIMESTAMP NULL DEFAULT NULL,
  updated_at   TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_apt_opname_no (no_opname),
  CONSTRAINT fk_apt_opname_apoteker FOREIGN KEY (apoteker_id) REFERENCES sys_pengguna (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE apt_opname_detail (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  opname_id       BIGINT UNSIGNED NOT NULL,
  stok_batch_id   BIGINT UNSIGNED NOT NULL,
  stok_sistem     DECIMAL(12,2) NOT NULL,
  stok_fisik      DECIMAL(12,2) NOT NULL,
  selisih         DECIMAL(12,2) NOT NULL COMMENT 'stok_fisik - stok_sistem',
  keterangan      VARCHAR(255) NULL,
  created_at      TIMESTAMP NULL DEFAULT NULL,
  updated_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_apt_opname_detail (opname_id, stok_batch_id),
  CONSTRAINT fk_apt_opname_det_opname FOREIGN KEY (opname_id)     REFERENCES apt_opname (id) ON DELETE CASCADE,
  CONSTRAINT fk_apt_opname_det_batch  FOREIGN KEY (stok_batch_id) REFERENCES apt_stok_batch (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. BIL_ : BILLING / KASIR
-- ============================================================================

CREATE TABLE bil_tagihan (
  id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  no_tagihan          VARCHAR(30) NOT NULL,
  kunjungan_id        BIGINT UNSIGNED NULL,
  penjualan_umum_id   BIGINT UNSIGNED NULL,
  subtotal            DECIMAL(15,2) NOT NULL DEFAULT 0,
  diskon              DECIMAL(15,2) NOT NULL DEFAULT 0,
  total               DECIMAL(15,2) NOT NULL DEFAULT 0,
  status              ENUM('belum_bayar','lunas','batal') NOT NULL DEFAULT 'belum_bayar',
  created_at          TIMESTAMP NULL DEFAULT NULL,
  updated_at          TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_bil_tagihan_no (no_tagihan),
  UNIQUE KEY uk_bil_tagihan_kunjungan (kunjungan_id),
  UNIQUE KEY uk_bil_tagihan_penjualan (penjualan_umum_id),
  CONSTRAINT fk_bil_tagihan_kunjungan FOREIGN KEY (kunjungan_id)      REFERENCES reg_kunjungan (id),
  CONSTRAINT fk_bil_tagihan_penjualan FOREIGN KEY (penjualan_umum_id) REFERENCES apt_penjualan_umum (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bil_tagihan_detail (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tagihan_id       BIGINT UNSIGNED NOT NULL,
  komponen         ENUM('konsultasi','tindakan','lab','obat','jasa_racik','lainnya') NOT NULL,
  referensi_tipe   VARCHAR(50) NULL,
  referensi_id     BIGINT UNSIGNED NULL,
  deskripsi        VARCHAR(255) NOT NULL,
  jumlah           DECIMAL(12,2) NOT NULL DEFAULT 1,
  harga_satuan     DECIMAL(15,2) NOT NULL,
  subtotal         DECIMAL(15,2) NOT NULL,
  created_at       TIMESTAMP NULL DEFAULT NULL,
  updated_at       TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_bil_tagihan_detail (tagihan_id),
  KEY idx_bil_tagihan_detail_ref (referensi_tipe, referensi_id),
  CONSTRAINT fk_bil_tagihan_detail FOREIGN KEY (tagihan_id) REFERENCES bil_tagihan (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bil_pembayaran (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tagihan_id        BIGINT UNSIGNED NOT NULL,
  metode_bayar_id   BIGINT UNSIGNED NOT NULL,
  jumlah_bayar      DECIMAL(15,2) NOT NULL,
  kembalian         DECIMAL(15,2) NOT NULL DEFAULT 0,
  no_referensi      VARCHAR(100) NULL,
  kasir_id          BIGINT UNSIGNED NOT NULL,
  dibayar_pada      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at        TIMESTAMP NULL DEFAULT NULL,
  updated_at        TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_bil_pembayaran_tagihan (tagihan_id),
  CONSTRAINT fk_bil_pembayaran_tagihan FOREIGN KEY (tagihan_id)      REFERENCES bil_tagihan (id),
  CONSTRAINT fk_bil_pembayaran_metode  FOREIGN KEY (metode_bayar_id) REFERENCES mtr_metode_bayar (id),
  CONSTRAINT fk_bil_pembayaran_kasir   FOREIGN KEY (kasir_id)        REFERENCES sys_pengguna (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. STS_ : INTEGRASI SATUSEHAT
-- ============================================================================

CREATE TABLE sts_sinkronisasi (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  jenis_resource    VARCHAR(50) NOT NULL COMMENT 'Encounter, Condition, dst',
  referensi_tabel   VARCHAR(100) NOT NULL,
  referensi_id      BIGINT UNSIGNED NOT NULL,
  status            ENUM('menunggu','berhasil','gagal') NOT NULL DEFAULT 'menunggu',
  ihs_id            VARCHAR(64) NULL,
  jumlah_percobaan  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  payload           JSON NULL,
  respons           JSON NULL,
  pesan_galat       TEXT NULL,
  terkirim_pada     TIMESTAMP NULL DEFAULT NULL,
  created_at        TIMESTAMP NULL DEFAULT NULL,
  updated_at        TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_sts_sinkronisasi_ref (referensi_tabel, referensi_id),
  KEY idx_sts_sinkronisasi_status (status, jenis_resource)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sts_token (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  token             TEXT NOT NULL COMMENT 'terenkripsi (cast encrypted)',
  kedaluwarsa_pada  TIMESTAMP NOT NULL,
  created_at        TIMESTAMP NULL DEFAULT NULL,
  updated_at        TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 9. DATA AWAL (SEEDER MINIMAL)
-- Catatan: sys_menu dan sys_hak_akses diisi lewat Laravel:
--   php artisan menu:sinkron  (menu dari config/menu.php + hak akses penuh admin)
--   php artisan db:seed --class=HakAksesAwalSeeder
-- ============================================================================

INSERT INTO sys_peran (nama, keterangan, created_at, updated_at) VALUES
 ('admin','Administrator sistem',NOW(),NOW()),
 ('resepsionis','Pendaftaran pasien',NOW(),NOW()),
 ('perawat','Pendaftaran dan TTV',NOW(),NOW()),
 ('dokter','Dokter spesialis',NOW(),NOW()),
 ('petugas_lab','Petugas laboratorium',NOW(),NOW()),
 ('apoteker','Apoteker / petugas farmasi',NOW(),NOW()),
 ('kasir','Kasir',NOW(),NOW());

INSERT INTO mtr_poli (kode, nama, created_at, updated_at) VALUES
 ('INT','Poli Penyakit Dalam',NOW(),NOW()),
 ('GIG','Poli Gigi',NOW(),NOW()),
 ('SRF','Poli Saraf',NOW(),NOW()),
 ('LAB','Laboratorium',NOW(),NOW());

INSERT INTO mtr_metode_bayar (nama, created_at, updated_at) VALUES
 ('Tunai',NOW(),NOW()),('Transfer Bank',NOW(),NOW()),('QRIS',NOW(),NOW());

INSERT INTO sys_pengaturan (kunci, nilai, keterangan, created_at, updated_at) VALUES
 ('biaya_jasa_racik','0','Biaya flat jasa racik per resep racikan (Rp)',NOW(),NOW()),
 ('retur_pasien_diizinkan','1','1 = retur obat dari pasien diizinkan',NOW(),NOW()),
 ('batas_peringatan_kedaluwarsa_hari','90','Peringatan obat mendekati kedaluwarsa',NOW(),NOW());
