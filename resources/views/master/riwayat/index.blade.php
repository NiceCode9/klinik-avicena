@extends('layouts.app')

@section('title', 'Riwayat Kunjungan')
@section('page-title', 'Riwayat Kunjungan')

@section('content')
    @php
        $nik = (string) $pasien->nik;
        $nikMasked = strlen($nik) >= 6 ? substr($nik, 0, 3) . str_repeat('*', strlen($nik) - 6) . substr($nik, -3) : str_repeat('*', strlen($nik));
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <p class="mb-1"><strong>{{ $pasien->nama_lengkap }}</strong> ({{ $pasien->no_rekam_medis }})</p>
            <p class="mb-0 text-muted">NIK: {{ $nikMasked }}</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Poli</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $kunjungan)
                        <tr>
                            <td>{{ $kunjungan->tanggal_kunjungan?->format('d-m-Y') }}</td>
                            <td><span class="badge text-bg-secondary">{{ $kunjungan->status }}</span></td>
                            <td>{{ $kunjungan->antrean->first()?->poli?->nama ?? '-' }}</td>
                            <td class="text-end pe-3">
                                <a href="{{ route('riwayat.show', [$pasien, $kunjungan]) }}" class="btn btn-sm btn-info">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Belum ada riwayat kunjungan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $data->links() }}</div>
@endsection