<?php

use App\Models\Master\Obat;
use App\Models\Master\Racikan;
use App\Models\Master\Satuan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('master.racikan', 'racikan.index', 'Menu Racikan');
});

function siapkanObat(string $kode, string $nama, Satuan $satuan, array $ganti = []): Obat
{
    return Obat::create(['kode' => $kode, 'nama' => $nama, 'satuan_id' => $satuan->id] + $ganti);
}

function dataRacikan(Obat $obat, Satuan $satuan, array $ganti = []): array
{
    return $ganti + [
        'nama_menu' => 'Puyer Batuk Anak',
        'keterangan' => 'Untuk batuk berdahak anak',
        'aktif' => '1',
        'bahan' => [
            ['obat_id' => $obat->id, 'jumlah' => '0.5', 'satuan_id' => $satuan->id],
        ],
    ];
}

test('pengguna dengan hak dapat membuka daftar menu racikan', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    Racikan::create(['nama_menu' => 'Puyer Batuk Anak']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.racikan']))
        ->get(route('racikan.index'))
        ->assertOk()
        ->assertSee('Puyer Batuk Anak');

    expect($satuan)->not->toBeNull();
});

test('pengguna tanpa hak ditolak membuka daftar menu racikan', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('racikan.index'))
        ->assertForbidden();
});

test('form tambah dan ubah menampilkan baris bahan', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    $paracetamol = siapkanObat('OBT-001', 'Paracetamol 500 mg', $satuan);
    $nonaktif = siapkanObat('OBT-002', 'CTM 4 mg', $satuan, ['aktif' => false]);

    $racikan = Racikan::create(['nama_menu' => 'Puyer Batuk Anak']);
    $racikan->racikanBahan()->create([
        'obat_id' => $nonaktif->id,
        'jumlah' => '1',
        'satuan_id' => $satuan->id,
    ]);

    $pengguna = penggunaDenganIzin(['dasbor', 'master.racikan']);

    $this->actingAs($pengguna)
        ->get(route('racikan.create'))
        ->assertOk()
        ->assertSee('Tambah Baris')
        ->assertSee('Paracetamol 500 mg');

    // Obat nonaktif yang masih dipakai template tetap muncul sebagai pilihan.
    $this->actingAs($pengguna)
        ->get(route('racikan.edit', $racikan))
        ->assertOk()
        ->assertSee('Puyer Batuk Anak')
        ->assertSee('CTM 4 mg');
});

test('pengguna dengan hak dapat menambah menu racikan beserta bahannya', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    $paracetamol = siapkanObat('OBT-001', 'Paracetamol 500 mg', $satuan);
    $ctm = siapkanObat('OBT-002', 'CTM 4 mg', $satuan);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.racikan']))
        ->post(route('racikan.store'), dataRacikan($paracetamol, $satuan, [
            'bahan' => [
                ['obat_id' => $paracetamol->id, 'jumlah' => '0.5', 'satuan_id' => $satuan->id],
                ['obat_id' => $ctm->id, 'jumlah' => '2', 'satuan_id' => $satuan->id],
            ],
        ]))
        ->assertRedirect(route('racikan.index'));

    $racikan = Racikan::firstWhere('nama_menu', 'Puyer Batuk Anak');
    expect($racikan)->not->toBeNull();

    $this->assertDatabaseHas('mtr_racikan_bahan', [
        'racikan_id' => $racikan->id,
        'obat_id' => $paracetamol->id,
        'jumlah' => 0.5,
        'satuan_id' => $satuan->id,
    ]);
    $this->assertDatabaseHas('mtr_racikan_bahan', [
        'racikan_id' => $racikan->id,
        'obat_id' => $ctm->id,
        'jumlah' => 2,
    ]);
});

test('baris bahan tanpa obat diabaikan', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    $paracetamol = siapkanObat('OBT-001', 'Paracetamol 500 mg', $satuan);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.racikan']))
        ->post(route('racikan.store'), dataRacikan($paracetamol, $satuan, [
            'bahan' => [
                ['obat_id' => $paracetamol->id, 'jumlah' => '0.5', 'satuan_id' => $satuan->id],
                ['obat_id' => '', 'jumlah' => '', 'satuan_id' => ''],
            ],
        ]))
        ->assertRedirect(route('racikan.index'));

    $racikan = Racikan::firstWhere('nama_menu', 'Puyer Batuk Anak');
    expect($racikan->racikanBahan()->count())->toBe(1);
});

test('update menyinkronkan bahan: ubah, tambah, dan hapus baris yang dibuang', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    $paracetamol = siapkanObat('OBT-001', 'Paracetamol 500 mg', $satuan);
    $ctm = siapkanObat('OBT-002', 'CTM 4 mg', $satuan);

    $racikan = Racikan::create(['nama_menu' => 'Puyer Batuk Anak']);
    $bahanParacetamol = $racikan->racikanBahan()->create([
        'obat_id' => $paracetamol->id,
        'jumlah' => '0.5',
        'satuan_id' => $satuan->id,
    ]);
    $bahanCtm = $racikan->racikanBahan()->create([
        'obat_id' => $ctm->id,
        'jumlah' => '1',
        'satuan_id' => $satuan->id,
    ]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.racikan']))
        ->put(route('racikan.update', $racikan), dataRacikan($paracetamol, $satuan, [
            'nama_menu' => 'Puyer Batuk Dewasa',
            'bahan' => [
                ['id' => $bahanParacetamol->id, 'obat_id' => $paracetamol->id, 'jumlah' => '1.5', 'satuan_id' => $satuan->id],
            ],
        ]))
        ->assertRedirect(route('racikan.index'));

    $this->assertDatabaseHas('mtr_racikan', ['id' => $racikan->id, 'nama_menu' => 'Puyer Batuk Dewasa']);
    $this->assertDatabaseHas('mtr_racikan_bahan', [
        'id' => $bahanParacetamol->id,
        'jumlah' => 1.5,
    ]);
    // Tabel bahan tidak punya soft delete: baris yang dibuang hilang permanen.
    $this->assertDatabaseMissing('mtr_racikan_bahan', ['id' => $bahanCtm->id]);
});

test('nama menu wajib diisi dan tidak boleh kembar', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    $paracetamol = siapkanObat('OBT-001', 'Paracetamol 500 mg', $satuan);
    Racikan::create(['nama_menu' => 'Puyer Batuk Anak']);
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.racikan']));

    $this->post(route('racikan.store'), dataRacikan($paracetamol, $satuan, ['nama_menu' => '']))
        ->assertSessionHasErrors('nama_menu');

    $this->post(route('racikan.store'), dataRacikan($paracetamol, $satuan))
        ->assertSessionHasErrors('nama_menu');
});

test('nama menu racikan sendiri boleh dipakai ulang saat diubah', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    $paracetamol = siapkanObat('OBT-001', 'Paracetamol 500 mg', $satuan);
    $racikan = Racikan::create(['nama_menu' => 'Puyer Batuk Anak']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.racikan']))
        ->put(route('racikan.update', $racikan), dataRacikan($paracetamol, $satuan, ['aktif' => '0']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('racikan.index'));

    $this->assertDatabaseHas('mtr_racikan', ['id' => $racikan->id, 'aktif' => false]);
});

test('racikan wajib punya minimal satu bahan', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    $paracetamol = siapkanObat('OBT-001', 'Paracetamol 500 mg', $satuan);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.racikan']))
        ->post(route('racikan.store'), dataRacikan($paracetamol, $satuan, ['bahan' => []]))
        ->assertSessionHasErrors('bahan');
});

test('obat yang sama tidak boleh dobel dalam satu racikan', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    $paracetamol = siapkanObat('OBT-001', 'Paracetamol 500 mg', $satuan);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.racikan']))
        ->post(route('racikan.store'), dataRacikan($paracetamol, $satuan, [
            'bahan' => [
                ['obat_id' => $paracetamol->id, 'jumlah' => '0.5', 'satuan_id' => $satuan->id],
                ['obat_id' => $paracetamol->id, 'jumlah' => '1', 'satuan_id' => $satuan->id],
            ],
        ]))
        ->assertSessionHasErrors('bahan');
});

test('jumlah, obat, dan satuan bahan divalidasi', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    $paracetamol = siapkanObat('OBT-001', 'Paracetamol 500 mg', $satuan);
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.racikan']));

    $this->post(route('racikan.store'), dataRacikan($paracetamol, $satuan, [
        'bahan' => [['obat_id' => $paracetamol->id, 'jumlah' => '0', 'satuan_id' => $satuan->id]],
    ]))->assertSessionHasErrors('bahan.0.jumlah');

    // Jumlah kosong tidak boleh lolos saat obat diisi (kolom jumlah NOT NULL).
    $this->post(route('racikan.store'), dataRacikan($paracetamol, $satuan, [
        'bahan' => [['obat_id' => $paracetamol->id, 'jumlah' => '', 'satuan_id' => $satuan->id]],
    ]))->assertSessionHasErrors('bahan.0.jumlah');

    $this->post(route('racikan.store'), dataRacikan($paracetamol, $satuan, [
        'bahan' => [['obat_id' => 999999, 'jumlah' => '1', 'satuan_id' => 999999]],
    ]))->assertSessionHasErrors(['bahan.0.obat_id', 'bahan.0.satuan_id']);
});

test('pengguna dengan hak dapat menghapus menu racikan (soft delete)', function () {
    $satuan = Satuan::create(['nama' => 'Tablet']);
    $paracetamol = siapkanObat('OBT-001', 'Paracetamol 500 mg', $satuan);

    $racikan = Racikan::create(['nama_menu' => 'Puyer Batuk Anak']);
    $bahan = $racikan->racikanBahan()->create([
        'obat_id' => $paracetamol->id,
        'jumlah' => '1',
        'satuan_id' => $satuan->id,
    ]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.racikan']))
        ->delete(route('racikan.destroy', $racikan))
        ->assertRedirect(route('racikan.index'));

    $this->assertSoftDeleted('mtr_racikan', ['id' => $racikan->id]);
    $this->assertDatabaseHas('mtr_racikan_bahan', ['id' => $bahan->id]);
});
