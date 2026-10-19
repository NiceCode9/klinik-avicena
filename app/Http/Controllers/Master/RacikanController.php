<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SimpanRacikanRequest;
use App\Models\Master\Obat;
use App\Models\Master\Racikan;
use App\Models\Master\Satuan;
use App\Services\Master\RacikanService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class RacikanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:master.racikan,lihat', only: ['index']),
            new Middleware('izin:master.racikan,tambah', only: ['create', 'store']),
            new Middleware('izin:master.racikan,ubah', only: ['edit', 'update']),
            new Middleware('izin:master.racikan,hapus', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('master.racikan.index', [
            'data' => Racikan::withCount('racikanBahan')->orderBy('nama_menu')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('master.racikan.create', [
            'daftarObat' => $this->pilihanObat(),
            'daftarSatuan' => $this->pilihanSatuan(),
        ]);
    }

    public function store(SimpanRacikanRequest $request, RacikanService $layanan): RedirectResponse
    {
        $layanan->buat($request->validated());

        return redirect()->route('racikan.index')->with('sukses', 'Data menu racikan berhasil ditambahkan.');
    }

    public function edit(Racikan $racikan): View
    {
        return view('master.racikan.edit', [
            'racikan' => $racikan->load('racikanBahan'),
            'daftarObat' => $this->pilihanObat($racikan),
            'daftarSatuan' => $this->pilihanSatuan(),
        ]);
    }

    public function update(
        SimpanRacikanRequest $request,
        Racikan $racikan,
        RacikanService $layanan,
    ): RedirectResponse {
        $layanan->perbarui($racikan, $request->validated());

        return redirect()->route('racikan.index')->with('sukses', 'Data menu racikan berhasil diperbarui.');
    }

    public function destroy(Racikan $racikan, RacikanService $layanan): RedirectResponse
    {
        $layanan->hapus($racikan);

        return redirect()->route('racikan.index')->with('sukses', 'Data menu racikan berhasil dihapus.');
    }

    /**
     * Obat nonaktif yang masih dipakai template ikut ditampilkan agar baris lama tidak berubah.
     *
     * @return Collection<int, Obat>
     */
    private function pilihanObat(?Racikan $racikan = null): Collection
    {
        return Obat::query()
            ->where('aktif', true)
            ->orWhereIn('id', $racikan?->racikanBahan->pluck('obat_id') ?? [])
            ->orderBy('nama')
            ->get();
    }

    /**
     * @return Collection<int, Satuan>
     */
    private function pilihanSatuan(): Collection
    {
        return Satuan::orderBy('nama')->get();
    }
}
