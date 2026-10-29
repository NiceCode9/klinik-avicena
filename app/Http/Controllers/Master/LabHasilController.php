<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Lab\Permintaan;
use App\Models\Lab\PermintaanDetail;
use App\Models\Master\Dokter;
use App\Models\Sys\LogAktivitas;
use App\Services\Lab\LabService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class LabHasilController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:lab.hasil,lihat', only: ['index', 'edit', 'cetak']),
            new Middleware('izin:lab.hasil,tambah', only: ['store', 'update']),
            new Middleware('izin:lab.hasil,ubah', only: ['kunci']),
        ];
    }

    public function index(): View
    {
        $permintaan = Permintaan::with(['kunjungan.pasien', 'dokter'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('lab.hasil.index', compact('permintaan'));
    }

    public function edit(Permintaan $permintaan): View
    {
        $permintaan->load(['kunjungan.pasien', 'dokter', 'permintaanDetail.pemeriksaanLab.parameterLab', 'permintaanDetail.hasil', 'permintaanDetail.spesimen']);

        return view('lab.hasil.edit', compact('permintaan'));
    }

    public function update(Request $request, Permintaan $permintaan, LabService $layanan): RedirectResponse
    {
        if ($permintaan->dikunci) {
            return redirect()->route('lab-hasil.edit', $permintaan)->with('galat', 'Data terkunci.');
        }

        $request->validate([
            'hasil' => ['nullable', 'array'],
        ]);

        try {
            foreach ((array) $request->input('hasil', []) as $detailId => $params) {
                $detail = PermintaanDetail::findOrFail((int) $detailId);
                foreach ((array) $params as $parameterId => $nilai) {
                    if (trim((string) $nilai) === '') {
                        continue;
                    }
                    $layanan->simpanHasil($detail, [
                        'parameter_lab_id' => (int) $parameterId,
                        'nilai' => $nilai,
                    ], $request->user());
                }
            }
        } catch (\Throwable $e) {
            return redirect()->route('lab-hasil.edit', $permintaan)->with('galat', $e->getMessage());
        }

        return redirect()->route('lab-hasil.edit', $permintaan)->with('sukses', 'Hasil disimpan.');
    }

    public function kunci(Request $request, Permintaan $permintaan, LabService $layanan): RedirectResponse
    {
        if ($request->filled('dokter_id')) {
            $request->validate(['dokter_id' => ['required', 'integer', 'exists:mtr_dokter,id']]);
        }

        try {
            $layanan->kunci($permintaan, $request->user(), $request->filled('dokter_id') ? Dokter::findOrFail((int) $request->input('dokter_id')) : null);
        } catch (\Throwable $e) {
            return redirect()->route('lab-hasil.edit', $permintaan)->with('galat', $e->getMessage());
        }

        return redirect()->route('lab-hasil.index')->with('sukses', 'Hasil dikunci.');
    }

    public function cetak(Permintaan $permintaan): View
    {
        if (! $permintaan->dikunci) {
            abort(403, 'Hasil lab belum dikunci.');
        }

        $permintaan->load(['kunjungan.pasien', 'dokter', 'permintaanDetail.pemeriksaanLab.parameterLab', 'permintaanDetail.hasil', 'permintaanDetail.spesimen']);

        LogAktivitas::catat(request()->user(), 'cetak_hasil_lab', 'lab_permintaan', $permintaan->id);

        return view('lab.hasil.cetak', compact('permintaan'));
    }
}
