@extends('layouts.app')

@section('title', 'Pemeriksaan (SOAP)')
@section('page-title', 'Pemeriksaan (SOAP)')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif
    @if (session('galat'))
        <div class="alert alert-danger">{{ session('galat') }}</div>
    @endif

    <div class="card">
        <div class="card-header">Antrean Menunggu & Sedang Diperiksa Hari Ini</div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Kunjungan</th>
                        <th>Pasien</th>
                        <th>Poli</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $kunjungan)
                        <tr>
                            <td>{{ $kunjungan->no_kunjungan }}</td>
                            <td>{{ $kunjungan->pasien?->nama_lengkap ?? '-' }}</td>
                            <td>{{ $kunjungan->pemeriksaan->first()?->poli?->nama ?? ($kunjungan->antrean->first()?->poli?->nama ?? '-') }}</td>
                            <td><span class="badge text-bg-secondary">{{ $kunjungan->status }}</span></td>
                            <td class="text-end pe-3">
                                @if ($kunjungan->pemeriksaan->isNotEmpty())
                                    <a href="{{ route('pemeriksaan.edit', $kunjungan->pemeriksaan->first()) }}" class="btn btn-sm btn-warning">Lanjut</a>
                                @else
                                    @izin('erm.pemeriksaan', 'tambah')
                                        <form method="post" action="{{ route('pemeriksaan.mulai', $kunjungan) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-primary">Mulai</button>
                                        </form>
                                    @endizin
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Tidak ada antrean.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $data->links() }}</div>
@endsection