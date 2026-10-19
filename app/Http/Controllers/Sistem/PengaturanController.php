<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sistem\SimpanPengaturanRequest;
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
            'pengaturan' => Pengaturan::orderBy('kunci')->get(),
        ]);
    }

    public function update(SimpanPengaturanRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            foreach ($request->validated()['nilai'] ?? [] as $kunci => $nilai) {
                Pengaturan::where('kunci', $kunci)->update(['nilai' => $nilai]);
            }
        });

        return redirect()->route('pengaturan.index')->with('sukses', 'Pengaturan berhasil disimpan.');
    }
}
