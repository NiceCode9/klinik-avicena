<?php

use App\Models\Sys\HakAkses;
use App\Models\Sys\Menu;
use App\Models\Sys\Peran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function buatMenu(string $kode, ?string $rute = null, ?string $nama = null): Menu
{
    return Menu::firstOrCreate(
        ['kode' => $kode],
        ['nama' => $nama ?? $kode, 'rute' => $rute ?? 'dashboard', 'aktif' => true],
    );
}

/**
 * Pengguna (default aktif) dengan peran baru yang punya hak penuh untuk menu-menu tersebut.
 */
function penggunaDenganIzin(array $kodeMenu = ['dasbor'], array $atribut = []): User
{
    $peran = Peran::create(['nama' => 'peran_'.Str::random(6)]);

    foreach ($kodeMenu as $kode) {
        $menu = buatMenu($kode);

        HakAkses::create([
            'peran_id' => $peran->id,
            'menu_id' => $menu->id,
            'bisa_lihat' => true,
            'bisa_tambah' => true,
            'bisa_ubah' => true,
            'bisa_hapus' => true,
        ]);
    }

    return User::factory()->create(['peran_id' => $peran->id] + $atribut);
}
