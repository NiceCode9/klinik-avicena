<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sistem\SimpanPenggunaRequest;
use App\Models\Master\Dokter;
use App\Models\Sys\Peran;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PenggunaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:sistem.pengguna,lihat', only: ['index']),
            new Middleware('izin:sistem.pengguna,tambah', only: ['create', 'store']),
            new Middleware('izin:sistem.pengguna,ubah', only: ['edit', 'update']),
            new Middleware('izin:sistem.pengguna,hapus', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        return view('sistem.pengguna.index', [
            'data' => User::with(['peran', 'dokter'])->orderBy('nama')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('sistem.pengguna.create', [
            'daftarPeran' => Peran::orderBy('nama')->get(),
            'daftarDokter' => $this->pilihanDokter(),
        ]);
    }

    public function store(SimpanPenggunaRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('pengguna.index')->with('sukses', 'Data pengguna berhasil ditambahkan.');
    }

    public function edit(User $pengguna): View
    {
        return view('sistem.pengguna.edit', [
            'pengguna' => $pengguna,
            'daftarPeran' => Peran::orderBy('nama')->get(),
            'daftarDokter' => $this->pilihanDokter($pengguna),
        ]);
    }

    public function update(SimpanPenggunaRequest $request, User $pengguna): RedirectResponse
    {
        $atribut = $request->validated();

        if (blank($atribut['password'] ?? null)) {
            unset($atribut['password']);
        }

        $tetapAdmin = (int) $atribut['peran_id'] === $pengguna->peran_id
            && (bool) ($atribut['aktif'] ?? false);

        if (! $tetapAdmin && $this->adminAktifTerakhir($pengguna)) {
            return back()
                ->with('gagal', 'Admin aktif terakhir tidak boleh dinonaktifkan atau diubah perannya.')
                ->withInput();
        }

        $pengguna->update($atribut);

        return redirect()->route('pengguna.index')->with('sukses', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $pengguna): RedirectResponse
    {
        if ($pengguna->is($request->user())) {
            return back()->with('gagal', 'Anda tidak dapat menghapus akun sendiri.');
        }

        if ($this->adminAktifTerakhir($pengguna)) {
            return back()->with('gagal', 'Admin aktif terakhir tidak boleh dihapus.');
        }

        $pengguna->delete();

        return redirect()->route('pengguna.index')->with('sukses', 'Data pengguna berhasil dihapus.');
    }

    /**
     * Dokter nonaktif yang sudah terpasang tetap tampil saat mengubah pengguna.
     *
     * @return Collection<int, Dokter>
     */
    private function pilihanDokter(?User $pengguna = null): Collection
    {
        return Dokter::query()
            ->where('aktif', true)
            ->when($pengguna?->dokter_id, fn ($query, $dokterId) => $query->orWhere('id', $dokterId))
            ->orderBy('nama')
            ->get();
    }

    /**
     * True bila pengguna adalah satu-satunya admin aktif yang tersisa.
     */
    private function adminAktifTerakhir(User $pengguna): bool
    {
        return $pengguna->aktif
            && $pengguna->peran?->nama === 'admin'
            && ! User::whereKeyNot($pengguna->id)
                ->where('aktif', true)
                ->whereHas('peran', fn ($query) => $query->where('nama', 'admin'))
                ->exists();
    }
}
