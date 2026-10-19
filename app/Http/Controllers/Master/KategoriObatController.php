<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SimpanKategoriObatRequest;
use App\Models\Master\KategoriObat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class KategoriObatController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:master.kategori_obat,lihat', only: ['index']),
            new Middleware('izin:master.kategori_obat,tambah', only: ['create', 'store']),
            new Middleware('izin:master.kategori_obat,ubah', only: ['edit', 'update']),
            new Middleware('izin:master.kategori_obat,hapus', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('master.kategori_obat.index', [
            'data' => KategoriObat::orderBy('nama')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('master.kategori_obat.create');
    }

    public function store(SimpanKategoriObatRequest $request): RedirectResponse
    {
        KategoriObat::create($request->validated());

        return redirect()->route('kategori-obat.index')->with('sukses', 'Data kategori obat berhasil ditambahkan.');
    }

    public function edit(KategoriObat $kategoriObat): View
    {
        return view('master.kategori_obat.edit', ['kategoriObat' => $kategoriObat]);
    }

    public function update(SimpanKategoriObatRequest $request, KategoriObat $kategoriObat): RedirectResponse
    {
        $kategoriObat->update($request->validated());

        return redirect()->route('kategori-obat.index')->with('sukses', 'Data kategori obat berhasil diperbarui.');
    }

    public function destroy(KategoriObat $kategoriObat): RedirectResponse
    {
        $kategoriObat->delete();

        return redirect()->route('kategori-obat.index')->with('sukses', 'Data kategori obat berhasil dihapus.');
    }
}
