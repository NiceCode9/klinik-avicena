<?php

use App\Models\Master\Dokter;
use App\Models\Master\Lokasi;
use App\Models\Master\Nakes;
use App\Models\Master\Obat;
use App\Models\Master\ParameterLab;
use App\Models\Master\PemeriksaanLab;
use App\Models\Master\Poli;
use App\Models\Master\Satuan;
use App\Services\SatuSehat\PemetaanService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('satusehat.pemetaan', 'satusehat-pemetaan.index', 'Pemetaan Terminologi');
});

/**
 * @return array<string, Model>
 */
function buatMasterBelumLengkap(): array
{
    $satuan = Satuan::create(['nama' => 'Tablet']);
    $obat = Obat::create(['kode' => 'OBT-1', 'nama' => 'Paracetamol', 'satuan_id' => $satuan->id]);
    $lab = PemeriksaanLab::create(['kode' => 'LAB-1', 'nama' => 'Darah Lengkap', 'tarif' => 0]);
    $parameter = ParameterLab::create(['pemeriksaan_lab_id' => $lab->id, 'nama_parameter' => 'Hemoglobin']);
    $poli = Poli::create(['kode' => 'INT', 'nama' => 'Poli Umum']);
    $dokter = Dokter::create(['nama' => 'dr. Budi', 'tarif_konsultasi' => 0]);
    $nakes = Nakes::create(['nama' => 'Ns. Rina', 'jenis' => 'perawat']);
    $lokasi = Lokasi::create(['kode' => 'APT', 'nama' => 'Apotek']);

    return compact('obat', 'lab', 'parameter', 'poli', 'dokter', 'nakes', 'lokasi');
}

test('setiap kategori mendeteksi item yang belum lengkap', function () {
    buatMasterBelumLengkap();

    $hasil = app(PemetaanService::class)->belumTerpetakan();

    foreach (['obat', 'pemeriksaan_lab', 'parameter_lab', 'poli', 'dokter', 'nakes', 'lokasi'] as $kategori) {
        expect($hasil['kategori'][$kategori]['jumlah'])->toBe(1);
    }
});

test('item yang sudah lengkap tidak muncul', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    Obat::create([
        'kode' => 'OBT-1',
        'nama' => 'Paracetamol',
        'satuan_id' => $satuan->id,
        'kode_kfa' => '93000001',
    ]);

    $lab = PemeriksaanLab::create([
        'kode' => 'LAB-1',
        'nama' => 'Darah Lengkap',
        'tarif' => 0,
        'kode_loinc' => '58410-2',
        'kode_spesimen' => '119297000',
    ]);

    ParameterLab::create([
        'pemeriksaan_lab_id' => $lab->id,
        'nama_parameter' => 'Hemoglobin',
        'kode_loinc' => '718-7',
        'satuan_ucum' => 'g/dL',
    ]);

    Poli::create([
        'kode' => 'INT',
        'nama' => 'Poli Umum',
        'kode_tipe_pelayanan' => '124',
        'ihs_location_id' => 'loc-1',
    ]);

    Dokter::create([
        'nama' => 'dr. Budi',
        'tarif_konsultasi' => 0,
        'ihs_practitioner_id' => 'prac-1',
    ]);

    Nakes::create([
        'nama' => 'Ns. Rina',
        'jenis' => 'perawat',
        'ihs_practitioner_id' => 'prac-2',
    ]);

    Lokasi::create([
        'kode' => 'APT',
        'nama' => 'Apotek',
        'ihs_location_id' => 'loc-2',
    ]);

    $hasil = app(PemetaanService::class)->belumTerpetakan();

    foreach ($hasil['kategori'] as $kategori) {
        expect($kategori['jumlah'])->toBe(0);
    }
});

test('item yang baru sebagian lengkap tetap muncul dengan kekurangan yang tepat', function () {
    PemeriksaanLab::create([
        'kode' => 'LAB-1',
        'nama' => 'Darah Lengkap',
        'tarif' => 0,
        'kode_loinc' => '58410-2',
    ]);

    $hasil = app(PemetaanService::class)->belumTerpetakan('pemeriksaan_lab');
    $baris = $hasil['kategori']['pemeriksaan_lab']['daftar']->first();

    expect($hasil['kategori']['pemeriksaan_lab']['jumlah'])->toBe(1)
        ->and($baris['kurang'])->toBe(['Kode Spesimen']);
});

test('service dapat memeriksa kekurangan satu item', function () {
    $data = buatMasterBelumLengkap();
    $service = app(PemetaanService::class);

    expect($service->kekurangan('obat', $data['obat']))->toBe(['Kode KFA aktual'])
        ->and($service->kekurangan('lokasi', $data['lokasi']))->toBe(['IHS Location'])
        ->and($service->kekurangan('tidak-ada', $data['obat']))->toBe([]);
});

test('parameter lab tertaut ke form edit pemeriksaan induk', function () {
    $data = buatMasterBelumLengkap();

    $hasil = app(PemetaanService::class)->belumTerpetakan('parameter_lab');
    $baris = $hasil['kategori']['parameter_lab']['daftar']->first();

    expect($baris['judul'])->toBe('Hemoglobin')
        ->and($baris['kode'])->toBe('LAB-1')
        ->and($baris['catatan'])->toBe('Darah Lengkap')
        ->and($baris['rute_edit'])->toBe(route('pemeriksaan-lab.edit', $data['lab']));
});

test('daftar dipaginasi per sepuluh baris', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);

    foreach (range(1, 12) as $i) {
        Obat::create(['kode' => "OBT-{$i}", 'nama' => "Obat {$i}", 'satuan_id' => $satuan->id]);
    }

    $hasil = app(PemetaanService::class)->belumTerpetakan('obat', 10);

    expect($hasil['kategori']['obat']['jumlah'])->toBe(12)
        ->and($hasil['kategori']['obat']['daftar']->count())->toBe(10)
        ->and($hasil['kategori']['obat']['daftar']->total())->toBe(12);
});

test('halaman menampilkan ringkasan dan tautan edit', function () {
    $data = buatMasterBelumLengkap();

    $this->actingAs(penggunaDenganIzin(['dasbor', 'satusehat.pemetaan']))
        ->get(route('satusehat-pemetaan.index', ['kategori' => 'obat']))
        ->assertOk()
        ->assertSee('Pemetaan Terminologi')
        ->assertSee('Paracetamol')
        ->assertSee(route('obat.edit', $data['obat']));
});

test('tamu dialihkan ke login', function () {
    $this->get(route('satusehat-pemetaan.index'))->assertRedirect(route('login'));
});

test('pengguna tanpa hak ditolak', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('satusehat-pemetaan.index'))
        ->assertForbidden();
});
