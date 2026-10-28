<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registrasi\SimpanLabMandiriRequest;
use App\Models\Lab\Permintaan;
use App\Models\Master\Dokter;
use App\Models\Master\PemeriksaanLab;
use App\Models\Master\Poli;
use App\Services\Lab\LabMandiriService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class LabMandiriController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:pendaftaran.lab_mandiri,lihat', only: ['index', 'show']),
            new Middleware('izin:pendaftaran.lab_mandiri,tambah', only: ['create', 'store', 'dokter']),
        ];
    }

    public function __construct(private LabMandiriService $layanan) {}

    public function index(): View
    {
        $permintaan = Permintaan::where('sumber', 'mandiri')
            ->with(['kunjungan.pasien', 'dokter'])
            ->latest()
            ->paginate(20);

        return view('pendaftaran.lab-mandiri.index', compact('permintaan'));
    }

    public function create(): View
    {
        $pemeriksaan = PemeriksaanLab::where('aktif', true)->orderBy('nama')->get();

        return view('pendaftaran.lab-mandiri.create', compact('pemeriksaan'));
    }

    public function store(SimpanLabMandiriRequest $request): RedirectResponse
    {
        $permintaan = $this->layanan->daftarkan($request->user(), $request->validated());

        return redirect()->route('lab-mandiri.index')
            ->with('sukses', 'Pendaftaran lab mandiri berhasil ('.$permintaan->no_permintaan.').');
    }

    public function dokter(): JsonResponse
    {
        $poliLabIds = Poli::where('kode', 'LAB')->pluck('id');

        $dokter = Dokter::where('aktif', true)
            ->orderByRaw('CASE WHEN poli_id IN (?) THEN 0 ELSE 1 END', [$poliLabIds])
            ->orderBy('nama')
            ->get(['id', 'nama', 'poli_id']);

        return response()->json($dokter);
    }
}
