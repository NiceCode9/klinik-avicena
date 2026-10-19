<?php

use App\Models\Erm\Diagnosis;
use App\Models\Erm\Pemeriksaan;
use App\Models\Master\Dokter;
use App\Models\Master\Icd10;
use App\Models\Master\Pasien;
use App\Models\Master\Poli;
use App\Models\Registrasi\Kunjungan;
use App\Models\Sys\HakAkses;
use App\Models\Sys\Peran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('master.icd10', 'icd10.index', 'ICD-10');
});

function dataIcd10(array $ganti = []): array
{
    return $ganti + ['kode' => 'A00', 'nama_diagnosis' => 'Kolera'];
}

/**
 * Membuat satu kode ICD-10 yang sudah dipakai pada diagnosis pasien.
 */
function icd10YangDipakai(): Icd10
{
    $icd10 = Icd10::create(['kode' => 'A00', 'nama_diagnosis' => 'Kolera']);
    $poli = Poli::create(['kode' => 'UMU', 'nama' => 'Umum']);
    $dokter = Dokter::create(['nama' => 'dr. Test', 'poli_id' => $poli->id]);
    $pasien = Pasien::create([
        'no_rekam_medis' => 'RM-0001',
        'nik' => '3201010101010001',
        // nik_hash disiapkan service pendaftaran (Fase 2); di tes cukup diisi manual.
        'nik_hash' => hash_hmac('sha256', '3201010101010001', (string) config('app.key')),
        'nama_lengkap' => 'Budi',
        'tanggal_lahir' => '1990-01-01',
        'jenis_kelamin' => 'L',
    ]);
    $kunjungan = Kunjungan::create([
        'no_kunjungan' => 'KJ-0001',
        'pasien_id' => $pasien->id,
        'tanggal_kunjungan' => today(),
    ]);
    $pemeriksaan = Pemeriksaan::create([
        'kunjungan_id' => $kunjungan->id,
        'poli_id' => $poli->id,
        'dokter_id' => $dokter->id,
    ]);
    Diagnosis::create(['pemeriksaan_id' => $pemeriksaan->id, 'icd10_id' => $icd10->id]);

    return $icd10;
}

function unggahCsv(string $isi): UploadedFile
{
    return UploadedFile::fake()->createWithContent('icd10.csv', $isi);
}

test('pengguna dengan hak dapat membuka daftar icd-10', function () {
    Icd10::create(dataIcd10());

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.icd10']))
        ->get(route('icd10.index'))
        ->assertOk()
        ->assertSee('A00')
        ->assertSee('Kolera');
});

test('pengguna tanpa hak ditolak membuka daftar icd-10', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('icd10.index'))
        ->assertForbidden();
});

test('daftar icd-10 dapat dicari berdasarkan kode dan nama', function () {
    Icd10::create(dataIcd10());
    Icd10::create(['kode' => 'A01', 'nama_diagnosis' => 'Tipes']);

    $pengguna = penggunaDenganIzin(['dasbor', 'master.icd10']);

    $this->actingAs($pengguna)
        ->get(route('icd10.index', ['cari' => 'A01']))
        ->assertOk()
        ->assertSee('Tipes')
        ->assertDontSee('Kolera');

    $this->actingAs($pengguna)
        ->get(route('icd10.index', ['cari' => 'Kolera']))
        ->assertOk()
        ->assertSee('Kolera')
        ->assertDontSee('Tipes');
});

test('form tambah dan ubah icd-10 tampil', function () {
    $icd10 = Icd10::create(dataIcd10());
    $pengguna = penggunaDenganIzin(['dasbor', 'master.icd10']);

    $this->actingAs($pengguna)
        ->get(route('icd10.create'))
        ->assertOk()
        ->assertSee('Kode ICD-10');

    $this->actingAs($pengguna)
        ->get(route('icd10.edit', $icd10))
        ->assertOk()
        ->assertSee('Kolera');
});

test('pengguna dengan hak dapat menambah icd-10', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.icd10']))
        ->post(route('icd10.store'), dataIcd10())
        ->assertRedirect(route('icd10.index'));

    $this->assertDatabaseHas('mtr_icd10', ['kode' => 'A00', 'nama_diagnosis' => 'Kolera']);
});

test('kode icd-10 wajib, unik, dan maksimal 10 karakter', function () {
    Icd10::create(dataIcd10());
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.icd10']));

    $this->post(route('icd10.store'), dataIcd10(['kode' => '']))
        ->assertSessionHasErrors('kode');

    $this->post(route('icd10.store'), dataIcd10())
        ->assertSessionHasErrors('kode');

    $this->post(route('icd10.store'), dataIcd10(['kode' => 'A00123456789']))
        ->assertSessionHasErrors('kode');
});

test('nama diagnosis wajib diisi', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.icd10']))
        ->post(route('icd10.store'), dataIcd10(['nama_diagnosis' => '']))
        ->assertSessionHasErrors('nama_diagnosis');
});

test('pengguna dengan hak dapat mengubah icd-10', function () {
    $icd10 = Icd10::create(dataIcd10());

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.icd10']))
        ->put(route('icd10.update', $icd10), dataIcd10(['nama_diagnosis' => 'Kolera, karena Vibrio cholerae']))
        ->assertRedirect(route('icd10.index'));

    $this->assertDatabaseHas('mtr_icd10', [
        'id' => $icd10->id,
        'nama_diagnosis' => 'Kolera, karena Vibrio cholerae',
    ]);
});

test('icd-10 yang sudah dipakai diagnosis tidak boleh dihapus', function () {
    $icd10 = icd10YangDipakai();

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.icd10']))
        ->delete(route('icd10.destroy', $icd10))
        ->assertRedirect(route('icd10.index'))
        ->assertSessionHas('gagal');

    $this->assertDatabaseHas('mtr_icd10', ['id' => $icd10->id]);
});

test('pengguna dengan hak dapat menghapus icd-10 yang belum dipakai', function () {
    $icd10 = Icd10::create(dataIcd10());

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.icd10']))
        ->delete(route('icd10.destroy', $icd10))
        ->assertRedirect(route('icd10.index'))
        ->assertSessionHas('sukses');

    $this->assertDatabaseMissing('mtr_icd10', ['id' => $icd10->id]);
});

test('impor tanpa hak tambah ditolak', function () {
    $menu = buatMenu('master.icd10', 'icd10.index', 'ICD-10');
    $peran = Peran::create(['nama' => 'peran_lihat_saja']);
    HakAkses::create([
        'peran_id' => $peran->id,
        'menu_id' => $menu->id,
        'bisa_lihat' => true,
        'bisa_tambah' => false,
        'bisa_ubah' => false,
        'bisa_hapus' => false,
    ]);

    $pengguna = User::factory()->create(['peran_id' => $peran->id]);

    $this->actingAs($pengguna)
        ->get(route('icd10.impor'))
        ->assertForbidden();

    $this->actingAs($pengguna)
        ->post(route('icd10.impor.simpan'), ['berkas' => unggahCsv("A00,Kolera\n")])
        ->assertForbidden();
});

test('impor csv koma dengan header dan bom menambah serta memperbarui data', function () {
    Icd10::create(['kode' => 'A00', 'nama_diagnosis' => 'Kolera (lama)']);

    $csv = "\xEF\xBB\xBFkode,nama_diagnosis\n"
        ."A00,Kolera\n"
        ."A09,Diare dan gastroenteritis\n";

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.icd10']))
        ->post(route('icd10.impor.simpan'), ['berkas' => unggahCsv($csv)])
        ->assertRedirect(route('icd10.impor'))
        ->assertSessionHas('ringkasan', fn (array $ringkasan) => $ringkasan['ditambah'] === 1
            && $ringkasan['diperbarui'] === 1
            && $ringkasan['dilewati'] === 0);

    $this->assertDatabaseHas('mtr_icd10', ['kode' => 'A00', 'nama_diagnosis' => 'Kolera']);
    $this->assertDatabaseHas('mtr_icd10', ['kode' => 'A09', 'nama_diagnosis' => 'Diare dan gastroenteritis']);
});

test('impor csv titik-koma tanpa header terbaca', function () {
    $csv = "A00;Kolera\n"
        ."A09;Diare dan gastroenteritis, asal infeksius\n";

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.icd10']))
        ->post(route('icd10.impor.simpan'), ['berkas' => unggahCsv($csv)])
        ->assertRedirect(route('icd10.impor'))
        ->assertSessionHas('ringkasan', fn (array $ringkasan) => $ringkasan['ditambah'] === 2);

    $this->assertDatabaseHas('mtr_icd10', ['kode' => 'A09', 'nama_diagnosis' => 'Diare dan gastroenteritis, asal infeksius']);
});

test('baris tidak valid pada impor dilewati dan dilaporkan', function () {
    $csv = "A00,Kolera\n"
        .",Tanpa kode\n"
        ."A00123456789,Kode terlalu panjang\n"
        ."A01,\n"
        ."A02,Tipes\n";

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.icd10']))
        ->post(route('icd10.impor.simpan'), ['berkas' => unggahCsv($csv)])
        ->assertSessionHas('ringkasan', fn (array $ringkasan) => $ringkasan['ditambah'] === 2
            && $ringkasan['dilewati'] === 3
            && array_column($ringkasan['rincianDilewati'], 'baris') === [2, 3, 4]);

    $this->assertDatabaseMissing('mtr_icd10', ['kode' => 'A00123456789']);
    $this->assertDatabaseHas('mtr_icd10', ['kode' => 'A02']);
});

test('kode dobel di dalam file: yang terakhir dipakai', function () {
    $csv = "A00,Kolera\n"
        ."A00,Kolera (revisi)\n";

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.icd10']))
        ->post(route('icd10.impor.simpan'), ['berkas' => unggahCsv($csv)])
        ->assertSessionHas('ringkasan', fn (array $ringkasan) => $ringkasan['ditambah'] === 1
            && $ringkasan['dilewati'] === 1
            && $ringkasan['rincianDilewati'][0]['baris'] === 1);

    $this->assertDatabaseHas('mtr_icd10', ['kode' => 'A00', 'nama_diagnosis' => 'Kolera (revisi)']);
    $this->assertDatabaseCount('mtr_icd10', 1);
});
