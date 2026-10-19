<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SimpanPemeriksaanLabRequest;
use App\Models\Master\PemeriksaanLab;
use App\Services\Master\PemeriksaanLabService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PemeriksaanLabController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:master.pemeriksaan_lab,lihat', only: ['index']),
            new Middleware('izin:master.pemeriksaan_lab,tambah', only: ['create', 'store']),
            new Middleware('izin:master.pemeriksaan_lab,ubah', only: ['edit', 'update']),
            new Middleware('izin:master.pemeriksaan_lab,hapus', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('master.pemeriksaan_lab.index', [
            'data' => PemeriksaanLab::withCount('parameterLab')->orderBy('kode')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('master.pemeriksaan_lab.create');
    }

    public function store(SimpanPemeriksaanLabRequest $request, PemeriksaanLabService $layanan): RedirectResponse
    {
        $layanan->buat($request->validated());

        return redirect()->route('pemeriksaan-lab.index')->with('sukses', 'Data pemeriksaan lab berhasil ditambahkan.');
    }

    public function edit(PemeriksaanLab $pemeriksaanLab): View
    {
        return view('master.pemeriksaan_lab.edit', [
            'pemeriksaanLab' => $pemeriksaanLab->load('parameterLab'),
        ]);
    }

    public function update(
        SimpanPemeriksaanLabRequest $request,
        PemeriksaanLab $pemeriksaanLab,
        PemeriksaanLabService $layanan,
    ): RedirectResponse {
        $layanan->perbarui($pemeriksaanLab, $request->validated());

        return redirect()->route('pemeriksaan-lab.index')->with('sukses', 'Data pemeriksaan lab berhasil diperbarui.');
    }

    public function destroy(PemeriksaanLab $pemeriksaanLab, PemeriksaanLabService $layanan): RedirectResponse
    {
        $layanan->hapus($pemeriksaanLab);

        return redirect()->route('pemeriksaan-lab.index')->with('sukses', 'Data pemeriksaan lab berhasil dihapus.');
    }
}
