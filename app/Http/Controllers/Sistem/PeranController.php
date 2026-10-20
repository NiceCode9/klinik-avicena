<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sistem\SimpanPeranRequest;
use App\Models\Sys\Peran;
use App\Services\IzinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PeranController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:sistem.peran,lihat', only: ['index']),
            new Middleware('izin:sistem.peran,tambah', only: ['create', 'store']),
            new Middleware('izin:sistem.peran,ubah', only: ['edit', 'update']),
            new Middleware('izin:sistem.peran,hapus', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('sistem.peran.index', [
            'data' => Peran::withCount('pengguna')->orderBy('nama')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('sistem.peran.create');
    }

    public function store(SimpanPeranRequest $request): RedirectResponse
    {
        Peran::create($request->validated());

        return redirect()->route('peran.index')->with('sukses', 'Data peran berhasil ditambahkan.');
    }

    public function edit(Peran $peran): View
    {
        return view('sistem.peran.edit', ['peran' => $peran]);
    }

    public function update(SimpanPeranRequest $request, Peran $peran): RedirectResponse
    {
        // Peran admin dipakai `menu:sinkron` dan seeder, jadi namanya dikunci.
        if ($peran->nama === 'admin' && $request->validated('nama') !== 'admin') {
            return back()->with('gagal', 'Nama peran admin tidak boleh diubah.')->withInput();
        }

        $peran->update($request->validated());

        return redirect()->route('peran.index')->with('sukses', 'Data peran berhasil diperbarui.');
    }

    public function destroy(Peran $peran): RedirectResponse
    {
        if ($peran->nama === 'admin') {
            return back()->with('gagal', 'Peran admin tidak boleh dihapus.');
        }

        if ($peran->pengguna()->exists()) {
            return back()->with('gagal', 'Peran masih dipakai pengguna sehingga tidak boleh dihapus.');
        }

        DB::transaction(function () use ($peran) {
            $peran->hakAkses()->delete();
            $peran->delete();
        });

        IzinService::lupakan($peran->id);

        return redirect()->route('peran.index')->with('sukses', 'Data peran berhasil dihapus.');
    }
}
