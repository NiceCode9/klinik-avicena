@extends('layouts.app')

@section('title', 'Hasil Lab')
@section('page-title', 'Hasil Lab')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif
    @if (session('galat'))
        <div class="alert alert-danger">{{ session('galat') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No Permintaan</th>
                        <th>Pasien</th>
                        <th>Dokter Lab</th>
                        <th>Status</th>
                        <th>Terkunci</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($permintaan as $p)
                        <tr>
                            <td>{{ $p->no_permintaan }}</td>
                            <td>{{ $p->kunjungan->pasien->nama_lengkap ?? '-' }}</td>
                            <td>{{ $p->dokter->nama ?? '-' }}</td>
                            <td>{{ $p->status }}</td>
                            <td>{{ $p->dikunci ? 'Ya' : 'Tidak' }}</td>
                            <td>
                                @izin('lab.hasil', 'lihat')
                                    <a href="{{ route('lab-hasil.edit', $p) }}" class="btn btn-sm btn-outline-secondary">Hasil</a>
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $permintaan->links() }}</div>
    </div>
@endsection