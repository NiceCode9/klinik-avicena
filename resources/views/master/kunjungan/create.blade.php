@extends('layouts.app')

@section('title', 'Daftar Kunjungan')
@section('page-title', 'Daftar Kunjungan')

@section('content')
    <div class="card">
        <div class="card-body">
            @if (! empty($nikError))
                <div class="alert alert-danger">{{ $nikError }}</div>
            @endif
            <form method="get" action="{{ route('kunjungan.create') }}" class="row g-2 mb-3">
                <div class="col-md-6">
                    <label for="nik" class="form-label">Cari Pasien (NIK)</label>
                    <input type="text" id="nik" name="nik" maxlength="16" inputmode="numeric"
                        class="form-control" value="{{ $nik }}" placeholder="16 digit NIK">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Cari</button>
                </div>
            </form>

            @if ($nik !== '')
                @if ($pasien)
                    <div class="alert alert-success">
                        Pasien ditemukan: <strong>{{ $pasien->nama_lengkap }}</strong>
                        ({{ $pasien->no_rekam_medis }}).
                    </div>
                    <form method="post" action="{{ route('kunjungan.store') }}">
                        @csrf
                        <input type="hidden" name="pasien_id" value="{{ $pasien->id }}">
                        <button type="submit" class="btn btn-primary">Buat Kunjungan</button>
                        <a href="{{ route('kunjungan.create') }}" class="btn btn-secondary">Batal</a>
                    </form>
                @elseif ($tidakDitemukan)
                    <div class="alert alert-warning">
                        Pasien dengan NIK tersebut belum terdaftar.
                        <a href="{{ route('pasien.create') }}" class="alert-link">Tambah pasien baru</a>.
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection