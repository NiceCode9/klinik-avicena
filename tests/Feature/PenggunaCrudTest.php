<?php

use App\Models\Master\Dokter;
use App\Models\Sys\Peran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    buatMenu('dasbor', 'dashboard', 'Dasbor');
    buatMenu('sistem.pengguna', 'pengguna.index', 'Pengguna');
});

function dataPengguna(Peran $peran, array $ganti = []): array
{
    return array_merge([
        'nama' => 'Petugas Satu',
        'username' => 'petugas1',
        'email' => 'petugas1@klinik.test',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'peran_id' => $peran->id,
        'aktif' => '1',
    ], $ganti);
}

function peranAdmin(): Peran
{
    return Peran::create(['nama' => 'admin']);
}

test('tamu dialihkan ke halaman login', function () {
    $this->get(route('pengguna.index'))->assertRedirect(route('login'));
});

test('pengguna dengan hak dapat membuka daftar pengguna', function () {
    $peran = Peran::create(['nama' => 'kasir']);
    User::factory()->create(['nama' => 'Petugas Kasir', 'username' => 'kasir1', 'peran_id' => $peran->id]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.pengguna']))
        ->get(route('pengguna.index'))
        ->assertOk()
        ->assertSee('Petugas Kasir')
        ->assertSee('kasir1');
});

test('pengguna tanpa hak ditolak membuka daftar pengguna', function () {
    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->get(route('pengguna.index'))
        ->assertForbidden();
});

test('pengguna dengan hak dapat menambah pengguna dan sandi tersimpan ter-hash', function () {
    $peran = Peran::create(['nama' => 'kasir']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.pengguna']))
        ->post(route('pengguna.store'), dataPengguna($peran))
        ->assertRedirect(route('pengguna.index'))
        ->assertSessionHas('sukses');

    $baru = User::where('username', 'petugas1')->sole();
    expect($baru->nama)->toBe('Petugas Satu')
        ->and($baru->peran_id)->toBe($peran->id)
        ->and($baru->aktif)->toBeTrue()
        ->and($baru->password)->not->toBe('rahasia123')
        ->and(Hash::check('rahasia123', $baru->password))->toBeTrue();
});

test('pengguna tanpa hak tambah tidak dapat menyimpan pengguna', function () {
    $peran = Peran::create(['nama' => 'kasir']);

    $this->actingAs(penggunaDenganIzin(['dasbor']))
        ->post(route('pengguna.store'), dataPengguna($peran))
        ->assertForbidden();

    $this->assertDatabaseMissing('sys_pengguna', ['username' => 'petugas1']);
});

test('data pengguna divalidasi', function () {
    $peran = Peran::create(['nama' => 'kasir']);
    $lain = User::factory()->create(['username' => 'dipakai', 'email' => 'dipakai@klinik.test']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.pengguna']));

    $this->post(route('pengguna.store'), dataPengguna($peran, ['nama' => '']))
        ->assertSessionHasErrors('nama');

    $this->post(route('pengguna.store'), dataPengguna($peran, ['username' => 'dipakai']))
        ->assertSessionHasErrors('username');

    $this->post(route('pengguna.store'), dataPengguna($peran, ['email' => 'bukan-email']))
        ->assertSessionHasErrors('email');

    $this->post(route('pengguna.store'), dataPengguna($peran, ['email' => 'dipakai@klinik.test']))
        ->assertSessionHasErrors('email');

    $this->post(route('pengguna.store'), dataPengguna($peran, ['password' => 'pendek', 'password_confirmation' => 'pendek']))
        ->assertSessionHasErrors('password');

    $this->post(route('pengguna.store'), dataPengguna($peran, ['password_confirmation' => 'beda-123']))
        ->assertSessionHasErrors('password');

    $this->post(route('pengguna.store'), dataPengguna($peran, ['peran_id' => 999]))
        ->assertSessionHasErrors('peran_id');
});

test('pengguna dapat memilih dokter pada form tambah', function () {
    Dokter::create(['nama' => 'dr. Ayu']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.pengguna']))
        ->get(route('pengguna.create'))
        ->assertOk()
        ->assertSee('dr. Ayu');
});

test('mengubah pengguna tanpa mengisi sandi tidak mengganti sandi', function () {
    $peran = Peran::create(['nama' => 'kasir']);
    $target = User::factory()->create([
        'nama' => 'Nama Lama',
        'username' => 'target1',
        'email' => 'target1@klinik.test',
        'password' => Hash::make('sandi-lama'),
        'peran_id' => $peran->id,
    ]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.pengguna']))
        ->put(route('pengguna.update', $target), dataPengguna($peran, [
            'nama' => 'Nama Baru',
            'username' => 'target1',
            'email' => 'target1@klinik.test',
            'password' => '',
            'password_confirmation' => '',
        ]))
        ->assertRedirect(route('pengguna.index'));

    $target->refresh();
    expect($target->nama)->toBe('Nama Baru')
        ->and(Hash::check('sandi-lama', $target->password))->toBeTrue();
});

test('mengubah pengguna dengan sandi baru mengganti sandi', function () {
    $peran = Peran::create(['nama' => 'kasir']);
    $target = User::factory()->create([
        'username' => 'target1',
        'email' => 'target1@klinik.test',
        'password' => Hash::make('sandi-lama'),
        'peran_id' => $peran->id,
    ]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.pengguna']))
        ->put(route('pengguna.update', $target), dataPengguna($peran, [
            'username' => 'target1',
            'email' => 'target1@klinik.test',
            'password' => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
        ]))
        ->assertRedirect(route('pengguna.index'));

    expect(Hash::check('sandi-baru-123', $target->fresh()->password))->toBeTrue();
});

test('pengguna dengan hak dapat menghapus pengguna (soft delete)', function () {
    $target = User::factory()->create();

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.pengguna']))
        ->delete(route('pengguna.destroy', $target))
        ->assertRedirect(route('pengguna.index'));

    $this->assertSoftDeleted('sys_pengguna', ['id' => $target->id]);
});

test('pengguna tidak dapat menghapus akun sendiri', function () {
    $pengguna = penggunaDenganIzin(['dasbor', 'sistem.pengguna']);

    $this->actingAs($pengguna)
        ->delete(route('pengguna.destroy', $pengguna))
        ->assertRedirect()
        ->assertSessionHas('gagal');

    $this->assertNotSoftDeleted('sys_pengguna', ['id' => $pengguna->id]);
});

test('admin aktif terakhir tidak boleh dihapus', function () {
    $admin = User::factory()->create(['peran_id' => peranAdmin()->id, 'aktif' => true]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.pengguna']))
        ->delete(route('pengguna.destroy', $admin))
        ->assertSessionHas('gagal');

    $this->assertNotSoftDeleted('sys_pengguna', ['id' => $admin->id]);
});

test('admin aktif terakhir tidak boleh dinonaktifkan atau diubah perannya', function () {
    $admin = User::factory()->create(['peran_id' => peranAdmin()->id, 'aktif' => true]);
    $peranLain = Peran::create(['nama' => 'kasir']);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.pengguna']));

    $this->put(route('pengguna.update', $admin), dataPengguna($admin->peran, [
        'nama' => $admin->nama,
        'username' => $admin->username,
        'email' => $admin->email,
        'password' => '',
        'password_confirmation' => '',
        'aktif' => '0',
    ]))->assertSessionHas('gagal');

    $this->assertDatabaseHas('sys_pengguna', ['id' => $admin->id, 'aktif' => true]);

    $this->put(route('pengguna.update', $admin), dataPengguna($peranLain, [
        'nama' => $admin->nama,
        'username' => $admin->username,
        'email' => $admin->email,
        'password' => '',
        'password_confirmation' => '',
    ]))->assertSessionHas('gagal');

    $this->assertDatabaseHas('sys_pengguna', ['id' => $admin->id, 'peran_id' => $admin->peran_id]);
});

test('admin boleh dihapus jika masih ada admin aktif lain', function () {
    $admin = User::factory()->create(['peran_id' => peranAdmin()->id, 'aktif' => true]);
    User::factory()->create(['peran_id' => $admin->peran_id, 'aktif' => true]);

    $this->actingAs(penggunaDenganIzin(['dasbor', 'sistem.pengguna']))
        ->delete(route('pengguna.destroy', $admin))
        ->assertRedirect(route('pengguna.index'));

    $this->assertSoftDeleted('sys_pengguna', ['id' => $admin->id]);
});
