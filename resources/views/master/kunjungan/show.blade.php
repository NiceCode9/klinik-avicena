@extends('layouts.app')

@section('title', 'Detail Kunjungan')
@section('page-title', 'Detail Kunjungan')

@section('content')
    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">No. Kunjungan</dt>
                <dd class="col-sm-9">{{ $kunjungan->no_kunjungan }}</dd>
                <dt class="col-sm-3">Pasien</dt>
                <dd class="col-sm-9">{{ $kunjungan->pasien?->nama_lengkap ?? '-' }} ({{ $kunjungan->pasien?->no_rekam_medis ?? '-' }})</dd>
                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">{{ $kunjungan->status }}</dd>
                <dt class="col-sm-3">Waktu Datang</dt>
                <dd class="col-sm-9">{{ $kunjungan->waktu_datang?->format('d-m-Y H:i') ?? '-' }}</dd>
            </dl>

            @if ($kunjungan->antrean->isNotEmpty())
                <hr>
                <h6>Nomor Antrean</h6>
                <ul class="list-unstyled">
                    @foreach ($kunjungan->antrean as $antrean)
                        <li>
                            <strong>{{ $antrean->nomor_antrean }}</strong> — {{ $antrean->poli?->nama ?? '-' }}
                            ({{ $antrean->status }})
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('kunjungan.cetak', $kunjungan) }}" class="btn btn-sm btn-outline-secondary" target="_blank">Cetak Nomor</a>
            @endif

            <div class="mt-3">
                <a href="{{ route('kunjungan.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </div>
@endsection