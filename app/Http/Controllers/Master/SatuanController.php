<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SimpanSatuanRequest;
use App\Models\Master\Satuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SatuanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:master.satuan,lihat', only: ['index']),
            new Middleware('izin:master.satuan,tambah', only: ['create', 'store']),
            new Middleware('izin:master.satuan,ubah', only: ['edit', 'update']),
            new Middleware('izin:master.satuan,hapus', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('master.satuan.index', [
            'data' => Satuan::orderBy('nama')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('master.satuan.create');
    }

    public function store(SimpanSatuanRequest $request): RedirectResponse
    {
        Satuan::create($request->validated());

        return redirect()->route('satuan.index')->with('sukses', 'Data satuan berhasil ditambahkan.');
    }

    public function edit(Satuan $satuan): View
    {
        return view('master.satuan.edit', ['satuan' => $satuan]);
    }

    public function update(SimpanSatuanRequest $request, Satuan $satuan): RedirectResponse
    {
        $satuan->update($request->validated());

        return redirect()->route('satuan.index')->with('sukses', 'Data satuan berhasil diperbarui.');
    }

    public function destroy(Satuan $satuan): RedirectResponse
    {
        $satuan->delete();

        return redirect()->route('satuan.index')->with('sukses', 'Data satuan berhasil dihapus.');
    }
}
