<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SimpanTtvRequest;
use App\Models\Master\Poli;
use App\Models\Registrasi\Kunjungan;
use App\Services\Registrasi\KunjunganService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class TtvController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:pendaftaran.ttv,lihat', only: ['index']),
            new Middleware('izin:pendaftaran.ttv,tambah', only: ['create', 'store']),
        ];
    }

    public function index(): View
    {
        $data = Kunjungan::with('pasien')
            ->whereDate('tanggal_kunjungan', today())
            ->where('status', 'terdaftar')
            ->orderByDesc('waktu_datang')
            ->paginate(15);

        return view('master.ttv.index', ['data' => $data]);
    }

    public function create(Kunjungan $kunjungan): View
    {
        $kunjungan->load('pasien');

        return view('master.ttv.create', [
            'kunjungan' => $kunjungan,
            'poli' => Poli::where('aktif', true)->orderBy('nama')->get(),
        ]);
    }

    public function store(SimpanTtvRequest $request, Kunjungan $kunjungan, KunjunganService $layanan): RedirectResponse
    {
        $poli = Poli::findOrFail($request->input('poli_id'));

        $layanan->simpanTtv($kunjungan, $poli, $request->only([
            'tekanan_darah_sistolik',
            'tekanan_darah_diastolik',
            'nadi',
            'suhu',
            'berat_badan',
            'tinggi_badan',
            'saturasi_oksigen',
        ]), $request->user());

        return redirect()->route('kunjungan.cetak', $kunjungan)
            ->with('sukses', 'TTV tersimpan. Nomor antrean terbit.');
    }
}
