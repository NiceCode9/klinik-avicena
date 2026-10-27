@extends('layouts.app')

@section('title', 'Rujukan Lab')
@section('page-title', 'Rujukan Lab')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Rujukan Lab</span>
            @izin('erm.rujukan_lab', 'tambah')
                <a href="{{ route('rujukan-lab.create') }}" class="btn btn-sm btn-primary ms-auto">Buat Rujukan</a>
            @endizin
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Pasien</th>
                        <th>Pemeriksaan Lab</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $r)
                        <tr>
                            <td>{{ $r->pemeriksaan?->pasien?->nama_lengkap ?? '-' }}</td>
                            <td>
                                @foreach ($r->rujukanLabDetail as $d)
                                    <span class="badge text-bg-light">{{ $d->pemeriksaanLab?->nama }}</span>
                                @endforeach
                            </td>
                            <td class="text-end pe-3">
                                <a href="{{ route('rujukan-lab.show', $r) }}" class="btn btn-sm btn-info">Lihat</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">Belum ada rujukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $data->links() }}</div>
@endsection