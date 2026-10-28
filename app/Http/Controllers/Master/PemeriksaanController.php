<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Erm\Pemeriksaan;
use App\Models\Master\Icd10;
use App\Models\Master\Tindakan;
use App\Models\Registrasi\Kunjungan;
use App\Models\User;
use App\Services\Erm\Exceptions\DataTerkunciException;
use App\Services\Erm\PemeriksaanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PemeriksaanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:erm.pemeriksaan,lihat', only: ['index', 'edit', 'icd10']),
            new Middleware('izin:erm.pemeriksaan,tambah', only: ['mulai']),
            new Middleware('izin:erm.pemeriksaan,ubah', only: ['update']),
        ];
    }

    public function index(Request $request): View
    {
        $data = Kunjungan::with(['pasien', 'antrean', 'pemeriksaan.poli'])
            ->whereDate('tanggal_kunjungan', today())
            ->whereIn('status', ['menunggu', 'diperiksa'])
            ->orderByDesc('waktu_datang')
            ->paginate(20);

        return view('master.pemeriksaan.index', ['data' => $data]);
    }

    public function mulai(Kunjungan $kunjungan, PemeriksaanService $layanan, Request $request): RedirectResponse
    {
        $dokter = $request->user()->dokter;
        if (! $dokter) {
            abort(403, 'Akun ini tidak terhubung ke data dokter.');
        }

        $pemeriksaan = $layanan->mulai($kunjungan, $dokter, $request->user());

        return redirect()->route('pemeriksaan.edit', $pemeriksaan)
            ->with('sukses', 'Pemeriksaan dimulai.');
    }

    public function edit(Pemeriksaan $pemeriksaan, Request $request): View
    {
        $pemeriksaan->load(['kunjungan.pasien', 'kunjungan.ttv', 'diagnosis.icd10', 'detailPenyakitDalam', 'detailSaraf', 'poli', 'tindakan.tindakan']);

        $tindakanMaster = Tindakan::where('aktif', true)
            ->where(function ($q) use ($pemeriksaan) {
                $q->whereNull('poli_id')->orWhere('poli_id', $pemeriksaan->poli_id);
            })
            ->orderBy('nama')
            ->get();
        $kunjungan = $pemeriksaan->kunjungan;
        $pasien = $kunjungan?->pasien;

        $riwayat = $pasien
            ? $pasien->kunjungan()->with('pemeriksaan.diagnosis.icd10')->orderByDesc('tanggal_kunjungan')->limit(5)->get()
            : collect();

        $bolehUbah = $this->bolehUbah($pemeriksaan, $request->user());

        return view('master.pemeriksaan.edit', [
            'pemeriksaan' => $pemeriksaan,
            'kunjungan' => $kunjungan,
            'pasien' => $pasien,
            'riwayat' => $riwayat,
            'bolehUbah' => $bolehUbah,
            'tindakanMaster' => $tindakanMaster,
        ]);
    }

    public function update(Pemeriksaan $pemeriksaan, Request $request, PemeriksaanService $layanan): RedirectResponse
    {
        if (! $this->bolehUbah($pemeriksaan, $request->user())) {
            abort(403);
        }

        $data = $request->only(['subjektif', 'objektif', 'asesmen', 'rencana', 'diagnosis', 'kode_cara_keluar', 'nama_cara_keluar']);

        // diagnosis comes as JSON string from form
        if (is_string($data['diagnosis'] ?? null)) {
            $data['diagnosis'] = json_decode($data['diagnosis'], true) ?: [];
        }

        try {
            if ($request->input('aksi') === 'final') {
                $layanan->simpanFinal($pemeriksaan, $data, $request->user());
            } else {
                $layanan->simpanDraf($pemeriksaan, $data, $request->user());
            }
        } catch (DataTerkunciException $e) {
            return back()->with('galat', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['diagnosis' => $e->getMessage()])->withInput();
        }

        return redirect()->route('pemeriksaan.index')->with('sukses', 'Pemeriksaan disimpan.');
    }

    public function icd10(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $query = Icd10::query();
        if ($q !== '') {
            $query->where(function ($b) use ($q) {
                $b->where('nama_diagnosis', 'like', '%'.$q.'%')
                    ->orWhere('kode', 'like', '%'.$q.'%');
            });
        }
        $hasil = $query->orderBy('kode')->limit(20)->get(['id', 'kode', 'nama_diagnosis']);

        return response()->json($hasil);
    }

    public function simpanDetailPd(Pemeriksaan $pemeriksaan, Request $request, PemeriksaanService $layanan): RedirectResponse
    {
        if (! $this->bolehUbah($pemeriksaan, $request->user())) {
            abort(403);
        }

        $data = $request->only(['riwayat_penyakit_kronis', 'status_metabolik', 'review_sistem_organ']);

        if (is_string($data['review_sistem_organ'] ?? null)) {
            $data['review_sistem_organ'] = json_decode($data['review_sistem_organ'], true);
        }

        try {
            $layanan->simpanDetailPenyakitDalam($pemeriksaan, $data, $request->user());
        } catch (DataTerkunciException $e) {
            return back()->with('galat', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['detail' => $e->getMessage()])->withInput();
        }

        return back()->with('sukses', 'Detail penyakit dalam disimpan.');
    }

    public function simpanDetailSaraf(Pemeriksaan $pemeriksaan, Request $request, PemeriksaanService $layanan): RedirectResponse
    {
        if (! $this->bolehUbah($pemeriksaan, $request->user())) {
            abort(403);
        }

        $data = $request->only(['evaluasi_motorik', 'evaluasi_sensorik', 'saraf_kranial']);

        foreach (['evaluasi_motorik', 'evaluasi_sensorik', 'saraf_kranial'] as $k) {
            if (is_string($data[$k] ?? null)) {
                $data[$k] = json_decode($data[$k], true);
            }
        }

        try {
            $layanan->simpanDetailSaraf($pemeriksaan, $data, $request->user());
        } catch (DataTerkunciException $e) {
            return back()->with('galat', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['detail' => $e->getMessage()])->withInput();
        }

        return back()->with('sukses', 'Detail saraf disimpan.');
    }

    public function simpanOdontogram(Pemeriksaan $pemeriksaan, Request $request, PemeriksaanService $layanan): RedirectResponse
    {
        if (! $this->bolehUbah($pemeriksaan, $request->user())) {
            abort(403);
        }

        $items = $request->input('items', []);
        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }

        try {
            $layanan->simpanOdontogram($pemeriksaan, ['items' => $items], $request->user());
        } catch (DataTerkunciException $e) {
            return back()->with('galat', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['odontogram' => $e->getMessage()])->withInput();
        }

        return back()->with('sukses', 'Odontogram disimpan.');
    }

    public function simpanTindakan(Pemeriksaan $pemeriksaan, Request $request, PemeriksaanService $layanan): RedirectResponse
    {
        if (! $this->bolehUbah($pemeriksaan, $request->user())) {
            abort(403);
        }

        $items = $request->input('items', []);
        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }

        try {
            $layanan->simpanTindakan($pemeriksaan, $items, $request->user());
        } catch (DataTerkunciException $e) {
            return back()->with('galat', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['tindakan' => $e->getMessage()])->withInput();
        }

        return back()->with('sukses', 'Tindakan disimpan.');
    }

    private function bolehUbah(Pemeriksaan $pemeriksaan, ?User $user): bool
    {
        if (! $user || ! $user->aktif) {
            return false;
        }
        if ($pemeriksaan->dikunci) {
            return false;
        }
        if ($user->peran?->nama === 'admin') {
            return true;
        }

        return $user->dokter_id !== null && $user->dokter_id === $pemeriksaan->dokter_id;
    }
}
