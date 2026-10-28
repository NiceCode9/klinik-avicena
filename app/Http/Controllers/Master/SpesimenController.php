<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Lab\Permintaan;
use App\Models\Lab\Spesimen;
use App\Services\Lab\SpesimenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SpesimenController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:lab.spesimen,lihat', only: ['index']),
            new Middleware('izin:lab.spesimen,tambah', only: ['create', 'store']),
            new Middleware('izin:lab.spesimen,ubah', only: ['edit', 'update']),
        ];
    }

    public function index(): View
    {
        $permintaan = Permintaan::with(['kunjungan.pasien', 'spesimen', 'permintaanDetail.pemeriksaanLab'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('lab.spesimen.index', compact('permintaan'));
    }

    public function create(Request $request): View
    {
        $permintaanId = $request->query('permintaan_id');

        return view('lab.spesimen.create', ['permintaanId' => $permintaanId]);
    }

    public function store(Request $request, SpesimenService $layanan): RedirectResponse
    {
        $data = $request->validate([
            'permintaan_id' => ['required', 'integer', 'exists:lab_permintaan,id'],
            'kode_spesimen' => ['nullable', 'string', 'max:30'],
            'nama_spesimen' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:menunggu_diambil,diambil,diterima,ditolak'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $permintaan = Permintaan::findOrFail((int) $data['permintaan_id']);
        $spesimen = $layanan->catat($permintaan, $data, $request->user());

        foreach ($permintaan->permintaanDetail()->get() as $detail) {
            $layanan->tautkan($detail, $spesimen);
        }

        return redirect()->route('spesimen.index')->with('sukses', 'Pengambilan spesimen dicatat.');
    }

    public function edit(Spesimen $spesimen): View
    {
        return view('lab.spesimen.edit', compact('spesimen'));
    }

    public function update(Request $request, Spesimen $spesimen, SpesimenService $layanan): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:menunggu_diambil,diambil,diterima,ditolak'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $layanan->ubahStatus($spesimen, $data['status'], $data['catatan'] ?? null, $request->user());

        return redirect()->route('spesimen.index')->with('sukses', 'Status spesimen diperbarui.');
    }
}
