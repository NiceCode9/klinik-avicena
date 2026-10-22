<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sistem\SimpanPengaturanRequest;
use App\Models\Sys\LogAktivitas;
use App\Models\Sys\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PengaturanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:sistem.pengaturan,lihat', only: ['index']),
            new Middleware('izin:sistem.pengaturan,ubah', only: ['update']),
        ];
    }

    public function index(): View
    {
        return view('sistem.pengaturan.index', [
            'pengaturan' => Pengaturan::whereNotIn('kunci', Pengaturan::TERSEMBUNYI)
                ->orderBy('kunci')
                ->get(),
        ]);
    }

    public function update(SimpanPengaturanRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            foreach ($request->validated()['nilai'] ?? [] as $kunci => $nilai) {
                if (in_array($kunci, Pengaturan::TERSEMBUNYI, true)) {
                    continue;
                }

                Pengaturan::where('kunci', $kunci)->update(['nilai' => $nilai]);
            }
        });

        // Update lewat query builder tidak memicu event model, jadi dicatat manual.
        LogAktivitas::catat($request->user(), 'ubah', 'sys_pengaturan');

        return redirect()->route('pengaturan.index')->with('sukses', 'Pengaturan berhasil disimpan.');
    }
}
