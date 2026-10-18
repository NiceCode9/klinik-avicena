<?php

namespace App\Console\Commands;

use App\Models\Sys\HakAkses;
use App\Models\Sys\Menu;
use App\Models\Sys\Peran;
use App\Services\IzinService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('menu:sinkron')]
#[Description('Sinkronkan tabel sys_menu dari config/menu.php dan beri akses penuh ke peran admin')]
class SinkronMenu extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        DB::transaction(function () {
            $dilihat = [];
            $this->simpan(config('menu'), null, $dilihat);

            // Menu yang dihapus dari config dinonaktifkan (bukan dihapus) agar hak akses lama tidak hilang.
            Menu::whereNotIn('kode', $dilihat)->update(['aktif' => false]);

            // Admin selalu memegang seluruh menu aktif supaya tidak pernah terkunci.
            if ($admin = Peran::where('nama', 'admin')->first()) {
                foreach (Menu::where('aktif', true)->pluck('id') as $menuId) {
                    HakAkses::updateOrCreate(
                        ['peran_id' => $admin->id, 'menu_id' => $menuId],
                        ['bisa_lihat' => true, 'bisa_tambah' => true, 'bisa_ubah' => true, 'bisa_hapus' => true],
                    );
                }
            }
        });

        Peran::pluck('id')->each(fn ($id) => IzinService::lupakan($id));
        $this->info('Menu tersinkron: '.Menu::where('aktif', true)->count().' menu aktif.');

        return self::SUCCESS;
    }

    private function simpan(array $daftar, ?int $indukId, array &$dilihat): void
    {
        foreach ($daftar as $urutan => $item) {
            $menu = Menu::updateOrCreate(['kode' => $item['kode']], [
                'induk_id' => $indukId,
                'nama' => $item['nama'],
                'ikon' => $item['ikon'] ?? null,
                'rute' => $item['rute'] ?? null,
                'urutan' => $urutan,
                'aktif' => true,
            ]);
            $dilihat[] = $item['kode'];
            $this->simpan($item['anak'] ?? [], $menu->id, $dilihat);
        }
    }
}
