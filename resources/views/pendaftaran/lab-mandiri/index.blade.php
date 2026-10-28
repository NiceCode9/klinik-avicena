@extends('layouts.app')

@section('title', 'Pendaftaran Lab Mandiri')
@section('page-title', 'Pendaftaran Lab Mandiri')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Daftar Pendaftaran Lab Mandiri</h5>
        @izin('pendaftaran.lab_mandiri', 'tambah')
            <a href="{{ route('lab-mandiri.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Baru</a>
        @endizin
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Permintaan</th>
                        <th>Pasien</th>
                        <th>Dokter Lab</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($permintaan as $p)
                        <tr>
                            <td>{{ $p->no_permintaan }}</td>
                            <td>{{ $p->kunjungan->pasien->nama_lengkap ?? '-' }}</td>
                            <td>{{ $p->dokter->nama ?? '-' }}</td>
                            <td>{{ $p->status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $permintaan->links() }}
        </div>
    </div>
@endsection