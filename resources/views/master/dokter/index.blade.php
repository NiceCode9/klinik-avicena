@extends('layouts.app')

@section('title', 'Dokter')
@section('page-title', 'Dokter')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Dokter</span>
            @izin('master.dokter', 'tambah')
                <a href="{{ route('dokter.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> Tambah Dokter
                </a>
            @endizin
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px">No</th>
                        <th>Nama</th>
                        <th style="width:110px">Poli</th>
                        <th style="width:150px">No. SIP</th>
                        <th class="text-end" style="width:150px">Tarif Konsultasi</th>
                        <th style="width:110px">Status</th>
                        <th class="text-end pe-3" style="width:180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $dokter)
                        <tr>
                            <td class="text-center">{{ $data->firstItem() + $loop->index }}</td>
                            <td>
                                {{ $dokter->nama }}
                                @if ($dokter->spesialisasi)
                                    <div class="small text-muted">{{ $dokter->spesialisasi }}</div>
                                @endif
                            </td>
                            <td>{{ $dokter->poli?->kode ?? '-' }}</td>
                            <td>{{ $dokter->no_sip ?? '-' }}</td>
                            <td class="text-end">{{ number_format($dokter->tarif_konsultasi, 0, ',', '.') }}</td>
                            <td>
                                @if ($dokter->aktif)
                                    <span class="badge text-bg-success">Aktif</span>
                                @else
                                    <span class="badge text-bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @izin('master.dokter', 'ubah')
                                    <a href="{{ route('dokter.edit', $dokter) }}" class="btn btn-sm btn-warning">Ubah</a>
                                @endizin
                                @izin('master.dokter', 'hapus')
                                    <form method="post" action="{{ route('dokter.destroy', $dokter) }}" class="d-inline"
                                        onsubmit="return confirm('Hapus dokter ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">Belum ada data dokter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $data->links() }}
    </div>
@endsection
