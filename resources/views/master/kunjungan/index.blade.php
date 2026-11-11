@extends('layouts.app')

@section('title', 'Kunjungan & Antrean')
@section('page-title', 'Kunjungan & Antrean')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Kunjungan Hari Ini</span>
            <div class="d-flex gap-2 ms-auto">
                <form method="get" action="{{ route('kunjungan.index') }}" class="d-flex gap-2">
                    <select name="status" class="form-select form-select-sm" style="width:160px">
                        <option value="">Semua status</option>
                        @foreach (['terdaftar','ttv','menunggu','diperiksa','lab','farmasi','selesai','batal'] as $s)
                            <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                </form>
                @izin('pendaftaran.kunjungan', 'tambah')
                    <a href="{{ route('kunjungan.create') }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg"></i> Daftar Kunjungan
                    </a>
                @endizin
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Kunjungan</th>
                        <th>Pasien</th>
                        <th>Status</th>
                        <th>Nomor Antrean</th>
                        <th>Waktu Datang</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $kunjungan)
                        <tr>
                            <td>{{ $kunjungan->no_kunjungan }}</td>
                            <td>{{ $kunjungan->pasien?->nama_lengkap ?? '-' }}</td>
                            <td><span class="badge text-bg-secondary">{{ $kunjungan->status }}</span></td>
                            <td>{{ $kunjungan->antrean->first()?->nomor_antrean ?? '-' }}</td>
                            <td>{{ $kunjungan->waktu_datang?->format('H:i') ?? '-' }}</td>
                            <td class="text-end pe-3">
                                <a href="{{ route('kunjungan.show', $kunjungan) }}" class="btn btn-sm btn-info">Lihat</a>
                                @if ($kunjungan->status === 'terdaftar')
                                    @izin('pendaftaran.ttv', 'tambah')
                                        <a href="{{ route('ttv.create', $kunjungan) }}" class="btn btn-sm btn-primary">Input TTV</a>
                                    @endizin
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Belum ada kunjungan hari ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $data->links() }}</div>
@endsection