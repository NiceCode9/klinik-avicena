<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SimpanLokasiRequest;
use App\Models\Master\Lokasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class LokasiController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:master.lokasi,lihat', only: ['index']),
            new Middleware('izin:master.lokasi,tambah', only: ['create', 'store']),
            new Middleware('izin:master.lokasi,ubah', only: ['edit', 'update']),
            new Middleware('izin:master.lokasi,hapus', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('master.lokasi.index', [
            'data' => Lokasi::orderBy('kode')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('master.lokasi.create');
    }

    public function store(SimpanLokasiRequest $request): RedirectResponse
    {
        Lokasi::create($request->validated());

        return redirect()->route('lokasi.index')->with('sukses', 'Data lokasi berhasil ditambahkan.');
    }

    public function edit(Lokasi $lokasi): View
    {
        return view('master.lokasi.edit', ['lokasi' => $lokasi]);
    }

    public function update(SimpanLokasiRequest $request, Lokasi $lokasi): RedirectResponse
    {
        $lokasi->update($request->validated());

        return redirect()->route('lokasi.index')->with('sukses', 'Data lokasi berhasil diperbarui.');
    }

    public function destroy(Lokasi $lokasi): RedirectResponse
    {
        $lokasi->delete();

        return redirect()->route('lokasi.index')->with('sukses', 'Data lokasi berhasil dihapus.');
    }
}
