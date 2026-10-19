<?php

/**
 * Daftar menu aplikasi (sumber kebenaran).
 * - 'kode'  : kontrak dengan middleware izin & sys_hak_akses. JANGAN diubah setelah dipakai.
 * - 'rute'  : NAMA route (bukan URL). Menu grup tidak punya 'rute'.
 * - Jalankan `php artisan menu:sinkron` setiap kali file ini berubah / saat deploy.
 */
return [
    ['kode' => 'dasbor', 'nama' => 'Dasbor', 'ikon' => 'bi-speedometer2', 'rute' => 'dashboard'],

    ['kode' => 'pendaftaran', 'nama' => 'Pendaftaran', 'ikon' => 'bi-person-plus', 'anak' => [
        ['kode' => 'pendaftaran.pasien',      'nama' => 'Data Pasien',             'rute' => 'pasien.index'],
        ['kode' => 'pendaftaran.kunjungan',   'nama' => 'Kunjungan & Antrean',     'rute' => 'kunjungan.index'],
        ['kode' => 'pendaftaran.ttv',         'nama' => 'Pemeriksaan TTV',         'rute' => 'ttv.index'],
        ['kode' => 'pendaftaran.lab_mandiri', 'nama' => 'Pendaftaran Lab Mandiri', 'rute' => 'lab-mandiri.index'],
    ]],

    ['kode' => 'erm', 'nama' => 'Poli & Rekam Medis', 'ikon' => 'bi-clipboard2-pulse', 'anak' => [
        ['kode' => 'erm.pemeriksaan', 'nama' => 'Pemeriksaan (SOAP)', 'rute' => 'pemeriksaan.index'],
        ['kode' => 'erm.resep',       'nama' => 'e-Resep',             'rute' => 'resep.index'],
        ['kode' => 'erm.rujukan_lab', 'nama' => 'Rujukan Lab',         'rute' => 'rujukan-lab.index'],
    ]],

    ['kode' => 'lab', 'nama' => 'Laboratorium', 'ikon' => 'bi-eyedropper', 'anak' => [
        ['kode' => 'lab.antrean', 'nama' => 'Antrean Lab', 'rute' => 'lab-antrean.index'],
        ['kode' => 'lab.hasil',   'nama' => 'Hasil Lab',   'rute' => 'lab-hasil.index'],
    ]],

    ['kode' => 'apotek', 'nama' => 'Apotek & Farmasi', 'ikon' => 'bi-capsule', 'anak' => [
        ['kode' => 'apotek.resep',          'nama' => 'Validasi & Racik Resep', 'rute' => 'apotek-resep.index'],
        ['kode' => 'apotek.penjualan',      'nama' => 'Penjualan Umum',         'rute' => 'penjualan-umum.index'],
        ['kode' => 'apotek.stok',           'nama' => 'Stok Obat',              'rute' => 'stok.index'],
        ['kode' => 'apotek.penerimaan',     'nama' => 'Obat Masuk',             'rute' => 'penerimaan.index'],
        ['kode' => 'apotek.retur_supplier', 'nama' => 'Retur ke Supplier',      'rute' => 'retur-supplier.index'],
        ['kode' => 'apotek.retur_pasien',   'nama' => 'Retur dari Pasien',      'rute' => 'retur-pasien.index'],
        ['kode' => 'apotek.opname',         'nama' => 'Stock Opname',           'rute' => 'opname.index'],
    ]],

    ['kode' => 'kasir', 'nama' => 'Kasir', 'ikon' => 'bi-cash-coin', 'anak' => [
        ['kode' => 'kasir.tagihan',    'nama' => 'Tagihan',       'rute' => 'tagihan.index'],
        ['kode' => 'kasir.pembayaran', 'nama' => 'Pembayaran',    'rute' => 'pembayaran.index'],
        ['kode' => 'kasir.laporan',    'nama' => 'Laporan Kasir', 'rute' => 'laporan-kasir.index'],
    ]],

    ['kode' => 'master', 'nama' => 'Data Master', 'ikon' => 'bi-database', 'anak' => [
        ['kode' => 'master.dokter',          'nama' => 'Dokter',          'rute' => 'dokter.index'],
        ['kode' => 'master.poli',            'nama' => 'Poli',            'rute' => 'poli.index'],
        ['kode' => 'master.obat',            'nama' => 'Obat',            'rute' => 'obat.index'],
        ['kode' => 'master.satuan',          'nama' => 'Satuan',          'rute' => 'satuan.index'],
        ['kode' => 'master.racikan',         'nama' => 'Menu Racikan',    'rute' => 'racikan.index'],
        ['kode' => 'master.supplier',        'nama' => 'Supplier / PBF',  'rute' => 'supplier.index'],
        ['kode' => 'master.tindakan',        'nama' => 'Tindakan Medis',  'rute' => 'tindakan.index'],
        ['kode' => 'master.pemeriksaan_lab', 'nama' => 'Pemeriksaan Lab', 'rute' => 'pemeriksaan-lab.index'],
        ['kode' => 'master.icd10',           'nama' => 'ICD-10',          'rute' => 'icd10.index'],
        ['kode' => 'master.metode_bayar',    'nama' => 'Metode Bayar',    'rute' => 'metode-bayar.index'],
    ]],

    ['kode' => 'satusehat', 'nama' => 'SATUSEHAT', 'ikon' => 'bi-cloud-arrow-up', 'anak' => [
        ['kode' => 'satusehat.sinkronisasi', 'nama' => 'Monitor & Re-sync', 'rute' => 'satusehat.index'],
    ]],

    ['kode' => 'sistem', 'nama' => 'Sistem', 'ikon' => 'bi-gear', 'anak' => [
        ['kode' => 'sistem.pengguna',   'nama' => 'Pengguna',      'rute' => 'pengguna.index'],
        ['kode' => 'sistem.hak_akses',  'nama' => 'Hak Akses',     'rute' => 'hak-akses.index'],
        ['kode' => 'sistem.pengaturan', 'nama' => 'Pengaturan',    'rute' => 'pengaturan.index'],
        ['kode' => 'sistem.log',        'nama' => 'Log Aktivitas', 'rute' => 'log-aktivitas.index'],
    ]],
];
