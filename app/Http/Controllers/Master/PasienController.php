<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SimpanPasienRequest;
use App\Models\Master\Pasien;
use App\Services\IzinService;
use App\Services\NikHasher;
use App\Services\Registrasi\PendaftaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PasienController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:pendaftaran.pasien,lihat', only: ['index', 'show']),
            new Middleware('izin:pendaftaran.pasien,tambah', only: ['create', 'store']),
            new Middleware('izin:pendaftaran.pasien,ubah', only: ['edit', 'update']),
        ];
    }

    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));

        $query = Pasien::query()->orderBy('nama_lengkap');

        if ($cari !== '') {
            $query->where(function ($builder) use ($cari) {
                $builder->where('nama_lengkap', 'like', '%'.$cari.'%')
                    ->orWhere('no_rekam_medis', 'like', '%'.$cari.'%');

                if (preg_match('/^\d{16}$/', $cari)) {
                    $builder->orWhere('nik_hash', NikHasher::hash($cari));
                }
            });
        }

        return view('master.pasien.index', [
            'data' => $query->paginate(10)->withQueryString(),
            'cari' => $cari,
        ]);
    }

    public function create(): View
    {
        return view('master.pasien.create');
    }

    public function store(SimpanPasienRequest $request, PendaftaranService $layanan): RedirectResponse
    {
        $layanan->daftarkanPasienBaru($request->validated(), $request->user());

        return redirect()->route('pasien.index')->with('sukses', 'Data pasien berhasil ditambahkan.');
    }

    public function show(Pasien $pasien): View
    {
        return view('master.pasien.show', [
            'pasien' => $pasien,
            'tampilkanNikPenuh' => IzinService::boleh(request()->user(), 'pendaftaran.pasien', 'ubah'),
        ]);
    }

    public function edit(Pasien $pasien): View
    {
        return view('master.pasien.edit', ['pasien' => $pasien]);
    }

    public function update(SimpanPasienRequest $request, Pasien $pasien, PendaftaranService $layanan): RedirectResponse
    {
        $layanan->perbaruiPasien($pasien, $request->validated(), $request->user());

        return redirect()->route('pasien.index')->with('sukses', 'Data pasien berhasil diperbarui.');
    }
}
