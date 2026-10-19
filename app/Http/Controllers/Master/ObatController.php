<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SimpanObatRequest;
use App\Models\Master\KategoriObat;
use App\Models\Master\Obat;
use App\Models\Master\Satuan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ObatController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:master.obat,lihat', only: ['index']),
            new Middleware('izin:master.obat,tambah', only: ['create', 'store']),
            new Middleware('izin:master.obat,ubah', only: ['edit', 'update']),
            new Middleware('izin:master.obat,hapus', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('master.obat.index', [
            'data' => Obat::with(['kategoriObat', 'satuan'])->orderBy('nama')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('master.obat.create', $this->pilihan());
    }

    public function store(SimpanObatRequest $request): RedirectResponse
    {
        Obat::create($request->validated());

        return redirect()->route('obat.index')->with('sukses', 'Data obat berhasil ditambahkan.');
    }

    public function edit(Obat $obat): View
    {
        return view('master.obat.edit', ['obat' => $obat] + $this->pilihan());
    }

    public function update(SimpanObatRequest $request, Obat $obat): RedirectResponse
    {
        $obat->update($request->validated());

        return redirect()->route('obat.index')->with('sukses', 'Data obat berhasil diperbarui.');
    }

    public function destroy(Obat $obat): RedirectResponse
    {
        $obat->delete();

        return redirect()->route('obat.index')->with('sukses', 'Data obat berhasil dihapus.');
    }

    /**
     * @return array{daftarKategori: Collection, daftarSatuan: Collection}
     */
    private function pilihan(): array
    {
        return [
            'daftarKategori' => KategoriObat::orderBy('nama')->get(),
            'daftarSatuan' => Satuan::orderBy('nama')->get(),
        ];
    }
}
