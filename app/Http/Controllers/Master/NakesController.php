<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SimpanNakesRequest;
use App\Models\Master\Nakes;
use App\Models\User;
use App\Services\Master\NakesService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class NakesController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:master.nakes,lihat', only: ['index']),
            new Middleware('izin:master.nakes,tambah', only: ['create', 'store']),
            new Middleware('izin:master.nakes,ubah', only: ['edit', 'update']),
            new Middleware('izin:master.nakes,hapus', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('master.nakes.index', [
            'data' => Nakes::with('pengguna')->orderBy('nama')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('master.nakes.create', ['daftarPengguna' => $this->daftarPengguna()]);
    }

    public function store(SimpanNakesRequest $request, NakesService $layanan): RedirectResponse
    {
        $layanan->buat($request->validated());

        return redirect()->route('nakes.index')->with('sukses', 'Data tenaga kesehatan berhasil ditambahkan.');
    }

    public function edit(Nakes $nakes): View
    {
        return view('master.nakes.edit', [
            'nakes' => $nakes,
            'daftarPengguna' => $this->daftarPengguna(),
        ]);
    }

    public function update(SimpanNakesRequest $request, Nakes $nakes, NakesService $layanan): RedirectResponse
    {
        $layanan->perbarui($nakes, $request->validated());

        return redirect()->route('nakes.index')->with('sukses', 'Data tenaga kesehatan berhasil diperbarui.');
    }

    public function destroy(Nakes $nakes, NakesService $layanan): RedirectResponse
    {
        $layanan->hapus($nakes);

        return redirect()->route('nakes.index')->with('sukses', 'Data tenaga kesehatan berhasil dihapus.');
    }

    private function daftarPengguna(): Collection
    {
        return User::orderBy('nama')->get();
    }
}
