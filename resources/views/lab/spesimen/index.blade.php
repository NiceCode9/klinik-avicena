@extends('layouts.app')

@section('title', 'Pengambilan Spesimen')
@section('page-title', 'Pengambilan Spesimen')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif
    @if (session('galat'))
        <div class="alert alert-danger">{{ session('galat') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Permintaan Lab &amp; Spesimen</h5>
        @izin('lab.spesimen', 'tambah')
            <a href="{{ route('spesimen.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Catat Spesimen</a>
        @endizin
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No Permintaan</th>
                        <th>Pasien</th>
                        <th>Spesimen</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($permintaan as $p)
                        <tr>
                            <td>{{ $p->no_permintaan }}</td>
                            <td>{{ $p->kunjungan->pasien->nama_lengkap ?? '-' }}</td>
                            <td>{{ $p->spesimen->first()->nama_spesimen ?? '-' }}</td>
                            <td>{{ $p->spesimen->first()->status ?? '-' }}</td>
                            <td>
                                @foreach ($p->spesimen as $s)
                                    <a href="{{ route('spesimen.edit', $s) }}" class="btn btn-sm btn-outline-secondary">Ubah Status</a>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $permintaan->links() }}</div>
    </div>
@endsection