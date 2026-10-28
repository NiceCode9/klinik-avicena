<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Lab\Permintaan;
use App\Services\Lab\LabService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class LabAntreanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:lab.antrean,lihat', only: ['index']),
            new Middleware('izin:lab.antrean,ubah', only: ['mulai', 'ubahDokter']),
        ];
    }

    public function index(): View
    {
        $permintaan = Permintaan::with(['antrean', 'kunjungan.pasien', 'dokter', 'permintaanDetail.pemeriksaanLab'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('lab.antrean.index', compact('permintaan'));
    }

    public function mulai(Permintaan $permintaan, Request $request, LabService $layanan): RedirectResponse
    {
        $layanan->mulaiProses($permintaan, $request->user());

        return redirect()->route('lab-antrean.index')->with('sukses', 'Proses lab dimulai.');
    }

    public function ubahDokter(Permintaan $permintaan, Request $request): RedirectResponse
    {
        $request->validate(['dokter_id' => ['required', 'integer', 'exists:mtr_dokter,id']]);

        if ($permintaan->dikunci) {
            return redirect()->route('lab-antrean.index')->with('galat', 'Data terkunci.');
        }

        $permintaan->update(['dokter_id' => (int) $request->input('dokter_id')]);

        return redirect()->route('lab-antrean.index')->with('sukses', 'Dokter lab diperbarui.');
    }
}
