@extends('layouts.app')

@section('title', 'Detail Pasien')
@section('page-title', 'Detail Pasien')

@section('content')
    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">No. Rekam Medis</dt>
                <dd class="col-sm-9">{{ $pasien->no_rekam_medis }}</dd>

                <dt class="col-sm-3">NIK</dt>
                <dd class="col-sm-9">
                    @php
                        $nik = (string) $pasien->nik;
                        $tampil = $tampilkanNikPenuh
                            ? $nik
                            : (strlen($nik) >= 6 ? substr($nik, 0, 3).str_repeat('*', strlen($nik) - 6).substr($nik, -3) : str_repeat('*', strlen($nik)));
                    @endphp
                    {{ $tampil }}
                </dd>

                <dt class="col-sm-3">Nama Lengkap</dt>
                <dd class="col-sm-9">{{ $pasien->nama_lengkap }}</dd>

                <dt class="col-sm-3">Tempat Lahir</dt>
                <dd class="col-sm-9">{{ $pasien->tempat_lahir ?? '-' }}</dd>

                <dt class="col-sm-3">Tanggal Lahir</dt>
                <dd class="col-sm-9">{{ $pasien->tanggal_lahir?->format('d-m-Y') ?? '-' }}</dd>

                <dt class="col-sm-3">Jenis Kelamin</dt>
                <dd class="col-sm-9">{{ $pasien->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>

                <dt class="col-sm-3">Golongan Darah</dt>
                <dd class="col-sm-9">{{ $pasien->golongan_darah ?? '-' }}</dd>

                <dt class="col-sm-3">Alamat</dt>
                <dd class="col-sm-9">{{ $pasien->alamat ?? '-' }}</dd>

                <dt class="col-sm-3">No. Telepon</dt>
                <dd class="col-sm-9">{{ $pasien->no_telepon ?? '-' }}</dd>

                <dt class="col-sm-3">Nama Wali</dt>
                <dd class="col-sm-9">{{ $pasien->nama_wali ?? '-' }}</dd>
            </dl>

            <div class="mt-3 d-flex gap-2">
                @izin('pendaftaran.pasien', 'ubah')
                    <a href="{{ route('pasien.edit', $pasien) }}" class="btn btn-warning">Ubah</a>
                @endizin
                <a href="{{ route('pasien.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </div>
@endsection