<?php

use App\Models\Master\Nakes;
use App\Models\User;
use App\Services\NikHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('master.nakes', 'nakes.index', 'Tenaga Kesehatan');
});

function dataNakes(array $ganti = []): array
{
    return $ganti + [
        'nama' => 'Ns. Rina',
        'jenis' => 'perawat',
        'nik' => '3201234567890001',
        'aktif' => '1',
    ];
}

test('pengguna dengan hak dapat membuka daftar tenaga kesehatan', function () {
    Nakes::create(['nama' => 'Ns. Rina', 'jenis' => 'perawat']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.nakes']))
        ->get(route('nakes.index'))
        ->assertOk()
        ->assertSee('Ns. Rina');
});

test('pengguna tanpa hak ditolak membuka daftar tenaga kesehatan', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('nakes.index'))
        ->assertForbidden();
});

test('NIK disimpan terenkripsi beserta hash untuk pencarian', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.nakes']))
        ->post(route('nakes.store'), dataNakes())
        ->assertRedirect(route('nakes.index'));

    $nakes = Nakes::firstWhere('nama', 'Ns. Rina');

    expect($nakes)->not->toBeNull()
        ->and($nakes->nik)->toBe('3201234567890001')
        ->and($nakes->nik_hash)->toBe(NikHasher::hash('3201234567890001'))
        ->and(DB::table('mtr_nakes')->where('id', $nakes->id)->value('nik'))->not->toBe('3201234567890001');
});

test('NIK duplikat ditolak', function () {
    Nakes::create(['nama' => 'Ns. Lama', 'jenis' => 'perawat', 'nik' => '3201234567890001']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.nakes']))
        ->post(route('nakes.store'), dataNakes(['nama' => 'Ns. Baru']))
        ->assertSessionHasErrors('nik');
});

test('NIK milik tenaga kesehatan yang sudah dihapus tetap ditolak', function () {
    $nakes = Nakes::create(['nama' => 'Ns. Lama', 'jenis' => 'perawat', 'nik' => '3201234567890001']);
    $nakes->delete();

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.nakes']))
        ->post(route('nakes.store'), dataNakes(['nama' => 'Ns. Baru']))
        ->assertSessionHasErrors('nik');
});

test('satu akun pengguna hanya boleh terhubung ke satu tenaga kesehatan', function () {
    $pengguna = User::factory()->create();
    Nakes::create([
        'nama' => 'Ns. Lama',
        'jenis' => 'perawat',
        'nik' => '3201234567890002',
        'pengguna_id' => $pengguna->id,
    ]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.nakes']))
        ->post(route('nakes.store'), dataNakes([
            'nama' => 'Ns. Baru',
            'nik' => '3201234567890003',
            'pengguna_id' => $pengguna->id,
        ]))
        ->assertSessionHasErrors('pengguna_id');
});

test('pengguna dengan hak dapat mengubah tenaga kesehatan', function () {
    $nakes = Nakes::create([
        'nama' => 'Ns. Rina',
        'jenis' => 'perawat',
        'nik' => '3201234567890001',
    ]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.nakes']))
        ->put(route('nakes.update', $nakes), dataNakes([
            'nama' => 'Ns. Rina Putri',
            'jenis' => 'apoteker',
            'nik' => '3201234567890009',
        ]))
        ->assertRedirect(route('nakes.index'));

    $nakes->refresh();

    expect($nakes->nama)->toBe('Ns. Rina Putri')
        ->and($nakes->jenis)->toBe('apoteker')
        ->and($nakes->nik)->toBe('3201234567890009')
        ->and($nakes->nik_hash)->toBe(NikHasher::hash('3201234567890009'));
});

test('pengguna dengan hak dapat menghapus tenaga kesehatan (soft delete)', function () {
    $nakes = Nakes::create(['nama' => 'Ns. Rina', 'jenis' => 'perawat']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'master.nakes']))
        ->delete(route('nakes.destroy', $nakes))
        ->assertRedirect(route('nakes.index'));

    $this->assertSoftDeleted('mtr_nakes', ['id' => $nakes->id]);
});
