@extends('layouts.app')

@section('title', 'Pemeriksaan TTV')
@section('page-title', 'Pemeriksaan TTV')

@section('content')
    <div class="card">
        <div class="card-header">Kunjungan Menunggu TTV Hari Ini</div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Kunjungan</th>
                        <th>Pasien</th>
                        <th>Waktu Datang</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $kunjungan)
                        <tr>
                            <td>{{ $kunjungan->no_kunjungan }}</td>
                            <td>{{ $kunjungan->pasien?->nama_lengkap ?? '-' }}</td>
                            <td>{{ $kunjungan->waktu_datang?->format('H:i') ?? '-' }}</td>
                            <td class="text-end pe-3">
                                @izin('pendaftaran.ttv', 'tambah')
                                    <a href="{{ route('ttv.create', $kunjungan) }}" class="btn btn-sm btn-primary">Input TTV</a>
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Tidak ada kunjungan menunggu TTV.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $data->links() }}</div>
@endsection