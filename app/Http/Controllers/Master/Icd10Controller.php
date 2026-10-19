<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ImporIcd10Request;
use App\Http\Requests\Master\SimpanIcd10Request;
use App\Models\Master\Icd10;
use App\Services\Master\Icd10ImporService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class Icd10Controller extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:master.icd10,lihat', only: ['index']),
            new Middleware('izin:master.icd10,tambah', only: ['create', 'store', 'impor', 'simpanImpor']),
            new Middleware('izin:master.icd10,ubah', only: ['edit', 'update']),
            new Middleware('izin:master.icd10,hapus', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));

        return view('master.icd10.index', [
            'data' => Icd10::query()
                ->when($cari !== '', function ($query) use ($cari) {
                    $pola = '%'.addcslashes($cari, '%_\\').'%';

                    $query->where(fn ($query) => $query
                        ->where('kode', 'like', $pola)
                        ->orWhere('nama_diagnosis', 'like', $pola));
                })
                ->orderBy('kode')
                ->paginate(20)
                ->withQueryString(),
            'cari' => $cari,
        ]);
    }

    public function create(): View
    {
        return view('master.icd10.create');
    }

    public function store(SimpanIcd10Request $request): RedirectResponse
    {
        Icd10::create($request->validated());

        return redirect()->route('icd10.index')->with('sukses', 'Data ICD-10 berhasil ditambahkan.');
    }

    public function edit(Icd10 $icd10): View
    {
        return view('master.icd10.edit', ['icd10' => $icd10]);
    }

    public function update(SimpanIcd10Request $request, Icd10 $icd10): RedirectResponse
    {
        $icd10->update($request->validated());

        return redirect()->route('icd10.index')->with('sukses', 'Data ICD-10 berhasil diperbarui.');
    }

    public function destroy(Icd10 $icd10): RedirectResponse
    {
        if ($icd10->diagnosis()->exists()) {
            return redirect()->route('icd10.index')
                ->with('gagal', 'Kode ini sudah dipakai pada diagnosis pasien sehingga tidak boleh dihapus.');
        }

        $icd10->delete();

        return redirect()->route('icd10.index')->with('sukses', 'Data ICD-10 berhasil dihapus.');
    }

    public function impor(): View
    {
        return view('master.icd10.impor');
    }

    public function simpanImpor(ImporIcd10Request $request, Icd10ImporService $layanan): RedirectResponse
    {
        return redirect()->route('icd10.impor')
            ->with('ringkasan', $layanan->impor($request->file('berkas')));
    }
}
