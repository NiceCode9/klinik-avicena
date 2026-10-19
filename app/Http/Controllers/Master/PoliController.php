<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SimpanPoliRequest;
use App\Models\Master\Poli;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PoliController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:master.poli,lihat', only: ['index']),
            new Middleware('izin:master.poli,tambah', only: ['create', 'store']),
            new Middleware('izin:master.poli,ubah', only: ['edit', 'update']),
            new Middleware('izin:master.poli,hapus', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('master.poli.index', [
            'data' => Poli::orderBy('kode')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('master.poli.create');
    }

    public function store(SimpanPoliRequest $request): RedirectResponse
    {
        Poli::create($request->validated());

        return redirect()->route('poli.index')->with('sukses', 'Data poli berhasil ditambahkan.');
    }

    public function edit(Poli $poli): View
    {
        return view('master.poli.edit', ['poli' => $poli]);
    }

    public function update(SimpanPoliRequest $request, Poli $poli): RedirectResponse
    {
        $poli->update($request->validated());

        return redirect()->route('poli.index')->with('sukses', 'Data poli berhasil diperbarui.');
    }

    public function destroy(Poli $poli): RedirectResponse
    {
        $poli->delete();

        return redirect()->route('poli.index')->with('sukses', 'Data poli berhasil dihapus.');
    }
}
