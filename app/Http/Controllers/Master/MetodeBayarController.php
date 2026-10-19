<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SimpanMetodeBayarRequest;
use App\Models\Master\MetodeBayar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class MetodeBayarController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:master.metode_bayar,lihat', only: ['index']),
            new Middleware('izin:master.metode_bayar,tambah', only: ['create', 'store']),
            new Middleware('izin:master.metode_bayar,ubah', only: ['edit', 'update']),
            new Middleware('izin:master.metode_bayar,hapus', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('master.metode_bayar.index', [
            'data' => MetodeBayar::orderBy('nama')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('master.metode_bayar.create');
    }

    public function store(SimpanMetodeBayarRequest $request): RedirectResponse
    {
        MetodeBayar::create($request->validated());

        return redirect()->route('metode-bayar.index')->with('sukses', 'Data metode bayar berhasil ditambahkan.');
    }

    public function edit(MetodeBayar $metodeBayar): View
    {
        return view('master.metode_bayar.edit', ['metodeBayar' => $metodeBayar]);
    }

    public function update(SimpanMetodeBayarRequest $request, MetodeBayar $metodeBayar): RedirectResponse
    {
        $metodeBayar->update($request->validated());

        return redirect()->route('metode-bayar.index')->with('sukses', 'Data metode bayar berhasil diperbarui.');
    }

    public function destroy(MetodeBayar $metodeBayar): RedirectResponse
    {
        $metodeBayar->delete();

        return redirect()->route('metode-bayar.index')->with('sukses', 'Data metode bayar berhasil dihapus.');
    }
}
