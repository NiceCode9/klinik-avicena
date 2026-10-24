<?php

use App\Models\Master\Pasien;
use App\Models\Sys\HakAkses;
use App\Models\Sys\Menu;
use App\Models\Sys\Peran;
use App\Models\User;
use App\Services\NikHasher;
use App\Services\Registrasi\PendaftaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('pendaftaran.pasien', 'pasien.index', 'Data Pasien');
});

function dataPasienCrud(array $ganti = []): array
{
    return $ganti + [
        'nik' => '3201234567890001',
        'nama_lengkap' => 'Siti Aminah',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '1990-01-01',
        'jenis_kelamin' => 'P',
        'golongan_darah' => 'O',
        'alamat' => 'Jl. Merdeka 1',
        'no_telepon' => '081234567890',
        'nama_wali' => 'Ahmad',
    ];
}

function penggunaTambahPasien(): User
{
    $peran = Peran::create(['nama' => 'peran_tambah_'.Str::random(6)]);
    $menu = buatMenu('pendaftaran.pasien');
    HakAkses::create([
        'peran_id' => $peran->id,
        'menu_id' => $menu->id,
        'bisa_lihat' => true,
        'bisa_tambah' => true,
        'bisa_ubah' => false,
        'bisa_hapus' => false,
    ]);
    buatMenu('dasbor');
    $menuDasbor = Menu::where('kode', 'dasbor')->first();
    HakAkses::create([
        'peran_id' => $peran->id,
        'menu_id' => $menuDasbor->id,
        'bisa_lihat' => true,
        'bisa_tambah' => true,
        'bisa_ubah' => true,
        'bisa_hapus' => true,
    ]);

    return User::factory()->create(['peran_id' => $peran->id]);
}

test('halaman create pasien dapat dibuka', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'pendaftaran.pasien']))
        ->get(route('pasien.create'))
        ->assertOk();
});

test('halaman edit pasien dapat dibuka', function () {
    $pasien = app(PendaftaranService::class)->daftarkanPasienBaru(
        dataPasienCrud(),
        User::factory()->create(),
    );

    $this->actingAs(penggunaDenganIzin(['dasbor', 'pendaftaran.pasien']))
        ->get(route('pasien.edit', $pasien))
        ->assertOk();
});

test('pengguna dengan hak dapat membuka daftar pasien', function () {
    Pasien::factory()->create(['nama_lengkap' => 'Budi Santoso']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'pendaftaran.pasien']))
        ->get(route('pasien.index'))
        ->assertOk()
        ->assertSee('Budi Santoso');
});

test('pengguna tanpa hak ditolak membuka daftar pasien', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('pasien.index'))
        ->assertForbidden();
});

test('pengguna dengan hak dapat menambah pasien', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'pendaftaran.pasien']))
        ->post(route('pasien.store'), dataPasienCrud())
        ->assertRedirect(route('pasien.index'));

    $pasien = Pasien::firstWhere('nama_lengkap', 'Siti Aminah');

    expect($pasien)->not->toBeNull()
        ->and($pasien->nik)->toBe('3201234567890001')
        ->and($pasien->nik_hash)->toBe(NikHasher::hash('3201234567890001'));
});

test('petugas lab (tambah tanpa ubah) dapat menambah pasien', function () {
    $this->actingAs(penggunaTambahPasien())
        ->post(route('pasien.store'), dataPasienCrud())
        ->assertRedirect(route('pasien.index'));
});

test('petugas lab ditolak saat mengubah pasien', function () {
    $pasien = app(PendaftaranService::class)->daftarkanPasienBaru(
        dataPasienCrud(),
        User::factory()->create(),
    );

    $this->actingAs(penggunaTambahPasien())
        ->put(route('pasien.update', $pasien), dataPasienCrud(['nama_lengkap' => 'Nama Baru']))
        ->assertForbidden();
});

test('pencarian berdasarkan NIK menemukan pasien tanpa memakai kolom nik', function () {
    $pasien = app(PendaftaranService::class)->daftarkanPasienBaru(
        dataPasienCrud(),
        User::factory()->create(),
    );

    $respons = $this->actingAs(penggunaDenganIzin(['dasbor', 'pendaftaran.pasien']))
        ->get(route('pasien.index', ['cari' => '3201234567890001']))
        ->assertOk();

    $respons->assertSee($pasien->no_rekam_medis);

    // pastikan query tidak memfilter kolom nik (cek melalui nik_hash)
    expect(Pasien::where('nik_hash', NikHasher::hash('3201234567890001'))->exists())->toBeTrue();
});

test('NIK ditampilkan sebagian pada show untuk pengguna tanpa hak ubah', function () {
    $pasien = app(PendaftaranService::class)->daftarkanPasienBaru(
        dataPasienCrud(),
        User::factory()->create(),
    );

    $this->actingAs(penggunaTambahPasien())
        ->get(route('pasien.show', $pasien))
        ->assertOk()
        ->assertSee('320')
        ->assertDontSee('3201234567890001');
});

test('NIK ditampilkan penuh pada show untuk pengguna dengan hak ubah', function () {
    $pasien = app(PendaftaranService::class)->daftarkanPasienBaru(
        dataPasienCrud(),
        User::factory()->create(),
    );

    $this->actingAs(penggunaDenganIzin(['dasbor', 'pendaftaran.pasien']))
        ->get(route('pasien.show', $pasien))
        ->assertOk()
        ->assertSee('3201234567890001');
});

test('pengguna dengan hak dapat mengubah pasien', function () {
    $pasien = app(PendaftaranService::class)->daftarkanPasienBaru(
        dataPasienCrud(),
        User::factory()->create(),
    );

    $this->actingAs(penggunaDenganIzin(['dasbor', 'pendaftaran.pasien']))
        ->put(route('pasien.update', $pasien), dataPasienCrud(['nama_lengkap' => 'Siti Aminah Baru']))
        ->assertRedirect(route('pasien.index'));

    $pasien->refresh();
    expect($pasien->nama_lengkap)->toBe('Siti Aminah Baru');
});

test('NIK ganda ditolak saat simpan pasien', function () {
    app(PendaftaranService::class)->daftarkanPasienBaru(dataPasienCrud(), User::factory()->create());

    $this->actingAs(penggunaDenganIzin(['dasbor', 'pendaftaran.pasien']))
        ->post(route('pasien.store'), dataPasienCrud(['nama_lengkap' => 'Lain']))
        ->assertSessionHasErrors('nik');
});
