<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SimpanRujukanLabRequest;
use App\Models\Erm\Pemeriksaan;
use App\Models\Erm\RujukanLab;
use App\Models\Master\PemeriksaanLab;
use App\Services\Erm\RujukanLabService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class RujukanLabController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:erm.rujukan_lab,lihat', only: ['index', 'show']),
            new Middleware('izin:erm.rujukan_lab,tambah', only: ['create', 'store']),
        ];
    }

    public function index(): View
    {
        $data = RujukanLab::with('pemeriksaan.pasien', 'rujukanLabDetail.pemeriksaanLab')->orderByDesc('created_at')->paginate(20);

        return view('master.rujukan_lab.index', ['data' => $data]);
    }

    public function create(Request $request): View
    {
        $pemeriksaan = null;
        if ($request->query('pemeriksaan_id')) {
            $pemeriksaan = Pemeriksaan::with('pasien')->find($request->query('pemeriksaan_id'));
        }

        $labList = PemeriksaanLab::where('aktif', true)->orderBy('nama')->get();

        return view('master.rujukan_lab.create', [
            'pemeriksaan' => $pemeriksaan,
            'labList' => $labList,
        ]);
    }

    public function store(SimpanRujukanLabRequest $request, RujukanLabService $layanan): RedirectResponse
    {
        $pemeriksaan = Pemeriksaan::findOrFail($request->input('pemeriksaan_id'));
        $layanan->buat($pemeriksaan, $request->input('pemeriksaan_lab_ids'), $request->input('catatan'), $request->user());

        return redirect()->route('rujukan-lab.index')->with('sukses', 'Rujukan lab dibuat.');
    }

    public function show(RujukanLab $rujukanLab): View
    {
        $rujukanLab->load('pemeriksaan.pasien', 'rujukanLabDetail.pemeriksaanLab', 'permintaan');

        return view('master.rujukan_lab.show', ['rujukanLab' => $rujukanLab]);
    }
}
