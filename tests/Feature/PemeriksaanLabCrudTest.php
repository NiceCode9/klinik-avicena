<?php

use App\Models\Master\PemeriksaanLab;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('master.pemeriksaan_lab', 'pemeriksaan-lab.index', 'Pemeriksaan Lab');
});

function dataPemeriksaan(array $ganti = []): array
{
    return $ganti + [
        'kode' => 'LAB-001',
        'nama' => 'Darah Lengkap',
        'tarif' => '75000',
        'aktif' => '1',
        'parameter' => [
            ['nama_parameter' => 'Hemoglobin', 'satuan' => 'g/dL', 'nilai_rujukan_min' => '12', 'nilai_rujukan_max' => '16'],
            ['nama_parameter' => 'Leukosit', 'satuan' => '10^3/uL', 'nilai_rujukan_min' => '4', 'nilai_rujukan_max' => '10'],
        ],
    ];
}

function buatPemeriksaan(array $ganti = []): PemeriksaanLab
{
    return PemeriksaanLab::create(['kode' => 'LAB-001', 'nama' => 'Darah Lengkap', 'tarif' => 75000] + $ganti);
}

test('pengguna dengan hak dapat membuka daftar pemeriksaan lab', function () {
    buatPemeriksaan();

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.pemeriksaan_lab']))
        ->get(route('pemeriksaan-lab.index'))
        ->assertOk()
        ->assertSee('Darah Lengkap');
});

test('pengguna tanpa hak ditolak membuka daftar pemeriksaan lab', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('pemeriksaan-lab.index'))
        ->assertForbidden();
});

test('form tambah dan ubah menampilkan baris parameter', function () {
    $pemeriksaan = buatPemeriksaan();
    $pemeriksaan->parameterLab()->create(['nama_parameter' => 'Hemoglobin', 'satuan' => 'g/dL', 'urutan' => 1]);

    $pengguna = penggunaDenganIzin(['dasbor', 'master.pemeriksaan_lab']);

    $this->actingAs($pengguna)
        ->get(route('pemeriksaan-lab.create'))
        ->assertOk()
        ->assertSee('Tambah Baris');

    $this->actingAs($pengguna)
        ->get(route('pemeriksaan-lab.edit', $pemeriksaan))
        ->assertOk()
        ->assertSee('Hemoglobin')
        ->assertSee('nama_parameter', false);
});

test('pengguna dengan hak dapat menambah pemeriksaan beserta parameternya', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.pemeriksaan_lab']))
        ->post(route('pemeriksaan-lab.store'), dataPemeriksaan())
        ->assertRedirect(route('pemeriksaan-lab.index'));

    $pemeriksaan = PemeriksaanLab::firstWhere('kode', 'LAB-001');
    expect($pemeriksaan)->not->toBeNull();

    $this->assertDatabaseHas('mtr_parameter_lab', [
        'pemeriksaan_lab_id' => $pemeriksaan->id,
        'nama_parameter' => 'Hemoglobin',
        'satuan' => 'g/dL',
        'urutan' => 1,
    ]);
    $this->assertDatabaseHas('mtr_parameter_lab', [
        'pemeriksaan_lab_id' => $pemeriksaan->id,
        'nama_parameter' => 'Leukosit',
        'urutan' => 2,
    ]);
});

test('baris parameter tanpa nama diabaikan', function () {
    $data = dataPemeriksaan();
    $data['parameter'][] = ['nama_parameter' => '', 'satuan' => ''];

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.pemeriksaan_lab']))
        ->post(route('pemeriksaan-lab.store'), $data)
        ->assertRedirect(route('pemeriksaan-lab.index'));

    $pemeriksaan = PemeriksaanLab::firstWhere('kode', 'LAB-001');
    expect($pemeriksaan->parameterLab()->count())->toBe(2);
});

test('update menyinkronkan parameter: ubah, tambah, dan soft delete baris yang dibuang', function () {
    $pemeriksaan = buatPemeriksaan();
    $hemoglobin = $pemeriksaan->parameterLab()->create([
        'nama_parameter' => 'Hemoglobin', 'satuan' => 'g/dL', 'urutan' => 1,
    ]);
    $leukosit = $pemeriksaan->parameterLab()->create([
        'nama_parameter' => 'Leukosit', 'satuan' => '10^3/uL', 'urutan' => 2,
    ]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.pemeriksaan_lab']))
        ->put(route('pemeriksaan-lab.update', $pemeriksaan), dataPemeriksaan([
            'parameter' => [
                ['id' => $hemoglobin->id, 'nama_parameter' => 'Hemoglobin (Hb)', 'satuan' => 'g/dL', 'urutan' => 9],
                ['nama_parameter' => 'Trombosit', 'satuan' => '10^3/uL'],
            ],
        ]))
        ->assertRedirect(route('pemeriksaan-lab.index'));

    $this->assertDatabaseHas('mtr_parameter_lab', [
        'id' => $hemoglobin->id,
        'nama_parameter' => 'Hemoglobin (Hb)',
        'urutan' => 1,
    ]);
    $this->assertDatabaseHas('mtr_parameter_lab', [
        'pemeriksaan_lab_id' => $pemeriksaan->id,
        'nama_parameter' => 'Trombosit',
        'urutan' => 2,
    ]);
    $this->assertSoftDeleted('mtr_parameter_lab', ['id' => $leukosit->id]);
});

test('kode pemeriksaan wajib, unik, dan tarif tidak boleh negatif', function () {
    buatPemeriksaan();
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.pemeriksaan_lab']));

    $this->post(route('pemeriksaan-lab.store'), dataPemeriksaan(['kode' => '', 'nama' => '']))
        ->assertSessionHasErrors(['kode', 'nama']);

    $this->post(route('pemeriksaan-lab.store'), dataPemeriksaan())
        ->assertSessionHasErrors('kode');

    $this->post(route('pemeriksaan-lab.store'), dataPemeriksaan(['kode' => 'LAB-002', 'tarif' => '-1']))
        ->assertSessionHasErrors('tarif');
});

test('pemeriksaan wajib punya minimal satu parameter bernama', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.pemeriksaan_lab']))
        ->post(route('pemeriksaan-lab.store'), dataPemeriksaan(['parameter' => []]))
        ->assertSessionHasErrors('parameter');
});

test('nama parameter tidak boleh kembar dalam satu pemeriksaan', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.pemeriksaan_lab']))
        ->post(route('pemeriksaan-lab.store'), dataPemeriksaan([
            'parameter' => [
                ['nama_parameter' => 'Hemoglobin'],
                ['nama_parameter' => 'Hemoglobin'],
            ],
        ]))
        ->assertSessionHasErrors('parameter');
});

test('nilai rujukan maksimum tidak boleh lebih kecil dari minimum', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.pemeriksaan_lab']))
        ->post(route('pemeriksaan-lab.store'), dataPemeriksaan([
            'parameter' => [
                ['nama_parameter' => 'Hemoglobin', 'nilai_rujukan_min' => '16', 'nilai_rujukan_max' => '12'],
            ],
        ]))
        ->assertSessionHasErrors('parameter.0.nilai_rujukan_max');
});

test('pengguna dengan hak dapat menghapus pemeriksaan (soft delete) tanpa menghapus parameter', function () {
    $pemeriksaan = buatPemeriksaan();
    $parameter = $pemeriksaan->parameterLab()->create(['nama_parameter' => 'Hemoglobin', 'urutan' => 1]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.pemeriksaan_lab']))
        ->delete(route('pemeriksaan-lab.destroy', $pemeriksaan))
        ->assertRedirect(route('pemeriksaan-lab.index'));

    $this->assertSoftDeleted('mtr_pemeriksaan_lab', ['id' => $pemeriksaan->id]);
    $this->assertDatabaseHas('mtr_parameter_lab', ['id' => $parameter->id, 'deleted_at' => null]);
});
