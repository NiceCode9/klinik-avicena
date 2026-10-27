@extends('layouts.app')

@section('title', 'Detail Rujukan Lab')
@section('page-title', 'Detail Rujukan Lab')

@section('content')
    <div class="card">
        <div class="card-body">
            <p>Pasien: <strong>{{ $rujukanLab->pemeriksaan?->pasien?->nama_lengkap ?? '-' }}</strong></p>
            <p>Pemeriksaan lab:</p>
            <ul>
                @foreach ($rujukanLab->rujukanLabDetail as $d)
                    <li>{{ $d->pemeriksaanLab?->nama }} ({{ $d->pemeriksaanLab?->kode }})</li>
                @endforeach
            </ul>
            @if ($rujukanLab->permintaan)
                <p>No. Permintaan: {{ $rujukanLab->permintaan->no_permintaan }} ({{ $rujukanLab->permintaan->status }})</p>
                <p>Nomor antrean: {{ $rujukanLab->permintaan->antrean?->nomor_antrean ?? '-' }}</p>
            @endif
            <a href="{{ route('rujukan-lab.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
@endsection