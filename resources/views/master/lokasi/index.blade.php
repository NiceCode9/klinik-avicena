@extends('layouts.app')

@section('title', 'Lokasi')
@section('page-title', 'Lokasi')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Lokasi</span>
            @izin('master.lokasi', 'tambah')
                <a href="{{ route('lokasi.create') }}" class="btn btn-sm btn-primary ms-auto">
                    <i class="bi bi-plus-lg"></i> Tambah Lokasi
                </a>
            @endizin
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px">No</th>
                        <th style="width:110px">Kode</th>
                        <th>Nama</th>
                        <th style="width:220px">IHS Location</th>
                        <th style="width:110px">Status</th>
                        <th class="text-end pe-3" style="width:180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $lokasi)
                        <tr>
                            <td class="text-center">{{ $data->firstItem() + $loop->index }}</td>
                            <td><code>{{ $lokasi->kode }}</code></td>
                            <td>{{ $lokasi->nama }}</td>
                            <td>{{ $lokasi->ihs_location_id ?? '-' }}</td>
                            <td>
                                @if ($lokasi->aktif)
                                    <span class="badge text-bg-success">Aktif</span>
                                @else
                                    <span class="badge text-bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @izin('master.lokasi', 'ubah')
                                    <a href="{{ route('lokasi.edit', $lokasi) }}" class="btn btn-sm btn-warning">Ubah</a>
                                @endizin
                                @izin('master.lokasi', 'hapus')
                                    <form method="post" action="{{ route('lokasi.destroy', $lokasi) }}" class="d-inline"
                                        onsubmit="return confirm('Hapus lokasi ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Belum ada data lokasi.</td>
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
