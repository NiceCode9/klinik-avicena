@extends('layouts.app')

@section('title', 'Detail Riwayat Kunjungan')
@section('page-title', 'Detail Riwayat Kunjungan')

@section('content')
    @php
        $nik = (string) $pasien->nik;
        $nikMasked = strlen($nik) >= 6 ? substr($nik, 0, 3) . str_repeat('*', strlen($nik) - 6) . substr($nik, -3) : str_repeat('*', strlen($nik));
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <p class="mb-1"><strong>{{ $pasien->nama_lengkap }}</strong> ({{ $pasien->no_rekam_medis }})</p>
            <p class="mb-0 text-muted">NIK: {{ $nikMasked }}</p>
            <p class="mb-0">No. Kunjungan: {{ $kunjungan->no_kunjungan }} ({{ $kunjungan->status }})</p>
        </div>
    </div>

    @if ($kunjungan->pemeriksaan->isNotEmpty())
        @php $pm = $kunjungan->pemeriksaan->first(); @endphp
        <div class="card mb-3">
            <div class="card-header">SOAP</div>
            <div class="card-body">
                <p><strong>S:</strong> {{ $pm->subjektif ?? '-' }}</p>
                <p><strong>O:</strong> {{ $pm->objektif ?? '-' }}</p>
                <p><strong>A:</strong> {{ $pm->asesmen ?? '-' }}</p>
                <p><strong>P:</strong> {{ $pm->rencana ?? '-' }}</p>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Diagnosis</div>
            <div class="card-body">
                @forelse ($pm->diagnosis as $d)
                    <span class="badge text-bg-light">{{ $d->icd10?->kode }} - {{ $d->icd10?->nama_diagnosis }} ({{ $d->jenis }})</span>
                @empty
                    <p class="text-muted mb-0">Tidak ada diagnosis.</p>
                @endforelse
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Tindakan</div>
            <div class="card-body">
                @forelse ($pm->tindakan as $t)
                    <p class="mb-1">{{ $t->tindakan?->nama }} x {{ $t->jumlah }} (Rp {{ number_format($t->tarif, 0, ',', '.') }})</p>
                @empty
                    <p class="text-muted mb-0">Tidak ada tindakan.</p>
                @endforelse
            </div>
        </div>

        @if ($pm->resep->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header">Resep</div>
                <div class="card-body">
                    @foreach ($pm->resep as $resep)
                        <p class="mb-1"><strong>{{ $resep->no_resep }}</strong> ({{ $resep->status }})</p>
                        <ul>
                            @foreach ($resep->resepItem as $item)
                                <li>{{ $item->jenis === 'reguler' ? ($item->obat?->nama ?? '-') : ($item->nama_racikan ?? '-') }} — {{ $item->jumlah }} — {{ $item->aturan_pakai }}</li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    @if ($kunjungan->permintaan->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">Hasil Lab</div>
            <div class="card-body">
                @foreach ($kunjungan->permintaan as $perm)
                    <p class="mb-1"><strong>{{ $perm->no_permintaan }}</strong> ({{ $perm->status }})</p>
                @endforeach
            </div>
        </div>
    @endif

    <a href="{{ route('riwayat.index', $pasien) }}" class="btn btn-secondary">Kembali</a>
@endsection