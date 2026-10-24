<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Pasien;
use App\Models\Registrasi\Kunjungan;
use App\Services\Registrasi\KunjunganService;
use App\Services\Registrasi\PendaftaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class KunjunganController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:pendaftaran.kunjungan,lihat', only: ['index', 'show', 'cetak']),
            new Middleware('izin:pendaftaran.kunjungan,tambah', only: ['create', 'store']),
        ];
    }

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');

        $query = Kunjungan::with('pasien', 'antrean')
            ->whereDate('tanggal_kunjungan', today())
            ->orderByDesc('waktu_datang');

        if ($status !== '') {
            $query->where('status', $status);
        }

        return view('master.kunjungan.index', [
            'data' => $query->paginate(15)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function create(Request $request): View
    {
        $nik = trim((string) $request->query('nik', ''));
        $pasien = null;
        $tidakDitemukan = false;

        if ($nik !== '') {
            $pasien = app(PendaftaranService::class)->cariByNik($nik);
            if ($pasien === null) {
                $tidakDitemukan = true;
            }
        }

        return view('master.kunjungan.create', [
            'nik' => $nik,
            'pasien' => $pasien,
            'tidakDitemukan' => $tidakDitemukan,
        ]);
    }

    public function store(Request $request, KunjunganService $layanan): RedirectResponse
    {
        $request->validate(['pasien_id' => ['required', 'exists:mtr_pasien,id']]);

        $pasien = Pasien::findOrFail($request->input('pasien_id'));
        $kunjungan = $layanan->buatKunjunganPoli($pasien, $request->user());

        return redirect()->route('ttv.create', ['kunjungan' => $kunjungan->id])
            ->with('sukses', 'Kunjungan dibuat. Silakan input TTV.');
    }

    public function show(Kunjungan $kunjungan): View
    {
        $kunjungan->load('pasien', 'antrean.poli', 'ttv');

        return view('master.kunjungan.show', ['kunjungan' => $kunjungan]);
    }

    public function cetak(Kunjungan $kunjungan): View
    {
        $kunjungan->load('pasien', 'antrean.poli');

        return view('master.kunjungan.cetak', ['kunjungan' => $kunjungan]);
    }
}
