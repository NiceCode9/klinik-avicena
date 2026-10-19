<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SimpanTindakanRequest;
use App\Models\Master\Poli;
use App\Models\Master\Tindakan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class TindakanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:master.tindakan,lihat', only: ['index']),
            new Middleware('izin:master.tindakan,tambah', only: ['create', 'store']),
            new Middleware('izin:master.tindakan,ubah', only: ['edit', 'update']),
            new Middleware('izin:master.tindakan,hapus', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('master.tindakan.index', [
            'data' => Tindakan::with('poli')->orderBy('kode')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('master.tindakan.create', ['daftarPoli' => $this->daftarPoli()]);
    }

    public function store(SimpanTindakanRequest $request): RedirectResponse
    {
        Tindakan::create($request->validated());

        return redirect()->route('tindakan.index')->with('sukses', 'Data tindakan berhasil ditambahkan.');
    }

    public function edit(Tindakan $tindakan): View
    {
        return view('master.tindakan.edit', [
            'tindakan' => $tindakan,
            'daftarPoli' => $this->daftarPoli(),
        ]);
    }

    public function update(SimpanTindakanRequest $request, Tindakan $tindakan): RedirectResponse
    {
        $tindakan->update($request->validated());

        return redirect()->route('tindakan.index')->with('sukses', 'Data tindakan berhasil diperbarui.');
    }

    public function destroy(Tindakan $tindakan): RedirectResponse
    {
        $tindakan->delete();

        return redirect()->route('tindakan.index')->with('sukses', 'Data tindakan berhasil dihapus.');
    }

    private function daftarPoli()
    {
        return Poli::orderBy('kode')->get();
    }
}
