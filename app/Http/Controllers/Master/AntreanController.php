<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Poli;
use App\Models\Registrasi\Antrean;
use App\Services\Registrasi\AntreanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class AntreanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:pendaftaran.antrean,lihat', only: ['index', 'json', 'layar']),
            new Middleware('izin:pendaftaran.antrean,ubah', only: ['panggil', 'layani', 'selesai', 'batal']),
        ];
    }

    public function layar(Request $request): View
    {
        $poliList = Poli::where('aktif', true)->orderBy('nama')->get();

        return view('master.antrean.layar', ['poliList' => $poliList]);
    }

    public function index(Request $request): View
    {
        $poliId = $request->query('poli_id');
        $poliList = Poli::where('aktif', true)->orderBy('nama')->get();

        $query = Antrean::with('poli', 'kunjungan.pasien')
            ->whereDate('tanggal', today())
            ->orderBy('poli_id')
            ->orderBy('nomor_urut');

        if ($poliId) {
            $query->where('poli_id', $poliId);
        }

        return view('master.antrean.index', [
            'data' => $query->get(),
            'poliList' => $poliList,
            'poliId' => $poliId,
        ]);
    }

    public function json(Request $request): JsonResponse
    {
        $poliId = $request->query('poli_id');

        $query = Antrean::with('kunjungan.pasien')
            ->whereDate('tanggal', today())
            ->orderBy('nomor_urut');

        if ($poliId) {
            $query->where('poli_id', $poliId);
        }

        $items = $query->get()->map(function (Antrean $antrean) {
            $nama = (string) ($antrean->kunjungan?->pasien?->nama_lengkap ?? '');

            return [
                'id' => $antrean->id,
                'nomor' => $antrean->nomor_antrean,
                'status' => $antrean->status,
                'inisial' => $nama !== '' ? mb_strtoupper(mb_substr($nama, 0, 1)) : '-',
            ];
        });

        $menunggu = $items->where('status', 'menunggu')->count();

        return response()->json([
            'items' => $items->values(),
            'menunggu' => $menunggu,
        ]);
    }

    public function panggil(Antrean $antrean, AntreanService $layanan): RedirectResponse
    {
        $layanan->panggil($antrean);

        return back()->with('sukses', 'Antrean dipanggil.');
    }

    public function layani(Antrean $antrean, AntreanService $layanan): RedirectResponse
    {
        $layanan->layani($antrean);

        return back()->with('sukses', 'Antrean dilayani.');
    }

    public function selesai(Antrean $antrean, AntreanService $layanan): RedirectResponse
    {
        $layanan->selesai($antrean);

        return back()->with('sukses', 'Antrean selesai.');
    }

    public function batal(Antrean $antrean, AntreanService $layanan): RedirectResponse
    {
        $layanan->batal($antrean);

        return back()->with('sukses', 'Antrean dibatalkan.');
    }
}
