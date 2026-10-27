<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Pasien;
use App\Models\Registrasi\Kunjungan;
use App\Models\Sys\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class RiwayatKunjunganController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:erm.pemeriksaan,lihat', only: ['index', 'show']),
        ];
    }

    public function index(Pasien $pasien): View
    {
        $data = Kunjungan::with(['pemeriksaan.diagnosis.icd10', 'pemeriksaan.tindakan.tindakan', 'pemeriksaan.resep.resepItem', 'antrean.poli'])
            ->where('pasien_id', $pasien->id)
            ->orderByDesc('tanggal_kunjungan')
            ->orderByDesc('id')
            ->paginate(10);

        return view('master.riwayat.index', [
            'pasien' => $pasien,
            'data' => $data,
        ]);
    }

    public function show(Pasien $pasien, Kunjungan $kunjungan, Request $request): View
    {
        $kunjungan->load([
            'pemeriksaan.diagnosis.icd10',
            'pemeriksaan.tindakan.tindakan',
            'pemeriksaan.resep.resepItem.obat',
            'pemeriksaan.odontogram',
            'permintaan.permintaanDetail.pemeriksaanLab',
            'antrean.poli',
            'ttv',
        ]);

        LogAktivitas::catat($request->user(), 'lihat_riwayat_pasien', 'reg_kunjungan', $kunjungan->id);

        return view('master.riwayat.show', [
            'pasien' => $pasien,
            'kunjungan' => $kunjungan,
        ]);
    }
}
