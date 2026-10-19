<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SimpanDokterRequest;
use App\Models\Master\Dokter;
use App\Models\Master\Poli;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class DokterController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:master.dokter,lihat', only: ['index']),
            new Middleware('izin:master.dokter,tambah', only: ['create', 'store']),
            new Middleware('izin:master.dokter,ubah', only: ['edit', 'update']),
            new Middleware('izin:master.dokter,hapus', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('master.dokter.index', [
            'data' => Dokter::with('poli')->orderBy('nama')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('master.dokter.create', ['daftarPoli' => $this->daftarPoli()]);
    }

    public function store(SimpanDokterRequest $request): RedirectResponse
    {
        Dokter::create($request->validated());

        return redirect()->route('dokter.index')->with('sukses', 'Data dokter berhasil ditambahkan.');
    }

    public function edit(Dokter $dokter): View
    {
        return view('master.dokter.edit', [
            'dokter' => $dokter,
            'daftarPoli' => $this->daftarPoli(),
        ]);
    }

    public function update(SimpanDokterRequest $request, Dokter $dokter): RedirectResponse
    {
        $dokter->update($request->validated());

        return redirect()->route('dokter.index')->with('sukses', 'Data dokter berhasil diperbarui.');
    }

    public function destroy(Dokter $dokter): RedirectResponse
    {
        $dokter->delete();

        return redirect()->route('dokter.index')->with('sukses', 'Data dokter berhasil dihapus.');
    }

    private function daftarPoli()
    {
        return Poli::orderBy('kode')->get();
    }
}
