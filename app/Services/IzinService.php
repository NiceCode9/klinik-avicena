<?php

namespace App\Services;

use App\Models\Sys\HakAkses;
use App\Models\Sys\Menu;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class IzinService
{
    public const AKSI = ['lihat', 'tambah', 'ubah', 'hapus'];

    /** Peta izin satu peran: ['kode_menu' => ['lihat' => bool, ...]] (di-cache). */
    public static function untukPeran(int $peranId): array
    {
        return Cache::remember(self::kunci($peranId), 3600, function () use ($peranId) {
            return HakAkses::query()
                ->join('sys_menu', 'sys_menu.id', '=', 'sys_hak_akses.menu_id')
                ->where('sys_hak_akses.peran_id', $peranId)
                ->where('sys_menu.aktif', true)
                ->get(['sys_menu.kode', 'sys_hak_akses.bisa_lihat', 'sys_hak_akses.bisa_tambah',
                    'sys_hak_akses.bisa_ubah', 'sys_hak_akses.bisa_hapus'])
                ->mapWithKeys(fn ($r) => [$r->kode => [
                    'lihat' => (bool) $r->bisa_lihat,
                    'tambah' => (bool) $r->bisa_tambah,
                    'ubah' => (bool) $r->bisa_ubah,
                    'hapus' => (bool) $r->bisa_hapus,
                ]])
                ->all();
        });
    }

    public static function boleh(?User $pengguna, string $kode, string $aksi = 'lihat'): bool
    {
        // Salah ketik aksi harus gagal keras saat development, bukan diam-diam menolak semua orang.
        if (! in_array($aksi, self::AKSI, true)) {
            throw new InvalidArgumentException("Aksi izin tidak dikenal: {$aksi}");
        }
        if (! $pengguna || ! $pengguna->aktif) {
            return false;
        }

        return self::untukPeran($pengguna->peran_id)[$kode][$aksi] ?? false;
    }

    public static function lupakan(int $peranId): void
    {
        Cache::forget(self::kunci($peranId));
    }

    /** Pohon menu sidebar yang hanya berisi menu yang boleh dilihat pengguna. */
    public static function pohonMenu(?User $pengguna): Collection
    {
        if (! $pengguna) {
            return collect();
        }

        $izin = self::untukPeran($pengguna->peran_id);
        $bisa = fn (Menu $m) => $m->rute && ($izin[$m->kode]['lihat'] ?? false);

        $semua = Menu::where('aktif', true)->orderBy('urutan')->get();
        $anak = $semua->whereNotNull('induk_id')->filter($bisa)->groupBy('induk_id');

        return $semua->whereNull('induk_id')
            ->map(function (Menu $m) use ($anak) {
                $m->setRelation('anak', $anak->get($m->id, collect()));

                return $m;
            })
            ->filter(fn (Menu $m) => $m->rute ? $bisa($m) : $m->anak->isNotEmpty())
            ->values();
    }

    private static function kunci(int $peranId): string
    {
        return "izin_peran_{$peranId}";
    }
}
