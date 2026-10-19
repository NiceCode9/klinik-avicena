<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Models\Sys\HakAkses;
use App\Models\Sys\LogAktivitas;
use App\Models\Sys\Menu;
use App\Models\Sys\Peran;
use App\Services\IzinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HakAksesController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:sistem.hak_akses,lihat', only: ['index']),
            new Middleware('izin:sistem.hak_akses,ubah', only: ['simpan']),
        ];
    }

    public function index(Request $request): View
    {
        // Peran admin tidak boleh diedit lewat UI agar tidak ada yang terkunci.
        $daftarPeran = Peran::where('nama', '!=', 'admin')->orderBy('nama')->get();
        $peran = $daftarPeran->firstWhere('id', $request->integer('peran')) ?? $daftarPeran->first();

        $grup = Menu::where('aktif', true)->whereNull('induk_id')->orderBy('urutan')
            ->with(['anak' => fn ($q) => $q->where('aktif', true)->orderBy('urutan')])
            ->get();

        $hak = $peran
            ? HakAkses::where('peran_id', $peran->id)->get()->keyBy('menu_id')
            : collect();

        return view('sistem.hak_akses.index', compact('daftarPeran', 'peran', 'grup', 'hak'));
    }

    public function simpan(Request $request, Peran $peran): RedirectResponse
    {
        abort_if($peran->nama === 'admin', 403, 'Hak akses admin tidak dapat diubah.');

        DB::transaction(function () use ($request, $peran) {
            // Hanya menu "daun" (punya rute) yang punya hak akses.
            foreach (Menu::where('aktif', true)->whereNotNull('rute')->pluck('id') as $menuId) {
                $in = (array) $request->input("izin.$menuId", []);
                $menulis = isset($in['tambah']) || isset($in['ubah']) || isset($in['hapus']);

                HakAkses::updateOrCreate(
                    ['peran_id' => $peran->id, 'menu_id' => $menuId],
                    [
                        // tambah/ubah/hapus otomatis membutuhkan akses lihat
                        'bisa_lihat' => isset($in['lihat']) || $menulis,
                        'bisa_tambah' => isset($in['tambah']),
                        'bisa_ubah' => isset($in['ubah']),
                        'bisa_hapus' => isset($in['hapus']),
                    ],
                );
            }
        });

        IzinService::lupakan($peran->id);
        LogAktivitas::catat($request->user(), 'ubah', 'sys_hak_akses', $peran->id);

        return redirect()->route('hak-akses.index', ['peran' => $peran->id])
            ->with('sukses', "Hak akses peran {$peran->nama} disimpan.");
    }
}
