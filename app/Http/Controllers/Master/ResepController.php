<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Erm\Pemeriksaan;
use App\Models\Erm\Resep;
use App\Services\Erm\ResepService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ResepController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:erm.resep,lihat', only: ['index', 'show']),
            new Middleware('izin:erm.resep,tambah', only: ['create', 'store']),
        ];
    }

    public function index(): View
    {
        $data = Resep::with('pemeriksaan.pasien', 'resepItem')->orderByDesc('created_at')->paginate(20);

        return view('master.resep.index', ['data' => $data]);
    }

    public function create(Request $request): View
    {
        $pemeriksaan = null;
        if ($request->query('pemeriksaan_id')) {
            $pemeriksaan = Pemeriksaan::with('pasien')->find($request->query('pemeriksaan_id'));
        }

        return view('master.resep.create', ['pemeriksaan' => $pemeriksaan]);
    }

    public function store(Request $request, ResepService $layanan): RedirectResponse
    {
        $request->validate(['pemeriksaan_id' => ['required', 'exists:erm_pemeriksaan,id']]);

        $pemeriksaan = Pemeriksaan::findOrFail($request->input('pemeriksaan_id'));
        $resep = $layanan->buatResep($pemeriksaan, $request->user());

        $items = $request->input('items', []);
        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }
        foreach ($items as $item) {
            $layanan->tambahItem($resep, $item, $request->user());
        }

        return redirect()->route('resep.show', $resep)->with('sukses', 'Resep dibuat.');
    }

    public function show(Resep $resep): View
    {
        $resep->load('pemeriksaan.pasien', 'resepItem.obat', 'resepItem.racikan', 'resepItem.resepRacikanBahan.obat');

        return view('master.resep.show', ['resep' => $resep]);
    }
}
