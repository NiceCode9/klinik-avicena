@extends('layouts.app')

@section('title', 'e-Resep')
@section('page-title', 'e-Resep')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Resep</span>
            @izin('erm.resep', 'tambah')
                <a href="{{ route('resep.create') }}" class="btn btn-sm btn-primary ms-auto">Buat Resep</a>
            @endizin
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Resep</th>
                        <th>Pasien</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $resep)
                        <tr>
                            <td>{{ $resep->no_resep }}</td>
                            <td>{{ $resep->pemeriksaan?->pasien?->nama_lengkap ?? '-' }}</td>
                            <td><span class="badge text-bg-secondary">{{ $resep->status }}</span></td>
                            <td class="text-end pe-3">
                                <a href="{{ route('resep.show', $resep) }}" class="btn btn-sm btn-info">Lihat</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Belum ada resep.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $data->links() }}</div>
@endsection