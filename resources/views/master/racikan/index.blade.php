@extends('layouts.app')

@section('title', 'Menu Racikan')
@section('page-title', 'Menu Racikan')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Menu Racikan</span>
            @izin('master.racikan', 'tambah')
                <a href="{{ route('racikan.create') }}" class="btn btn-sm btn-primary ms-auto">
                    <i class="bi bi-plus-lg"></i> Tambah Menu
                </a>
            @endizin
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px">No</th>
                        <th>Nama Menu</th>
                        <th>Keterangan</th>
                        <th class="text-center" style="width:100px">Bahan</th>
                        <th style="width:110px">Status</th>
                        <th class="text-end pe-3" style="width:180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $racikan)
                        <tr>
                            <td class="text-center">{{ $data->firstItem() + $loop->index }}</td>
                            <td>{{ $racikan->nama_menu }}</td>
                            <td class="text-muted">{{ $racikan->keterangan ?: '-' }}</td>
                            <td class="text-center">{{ $racikan->racikan_bahan_count }}</td>
                            <td>
                                @if ($racikan->aktif)
                                    <span class="badge text-bg-success">Aktif</span>
                                @else
                                    <span class="badge text-bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @izin('master.racikan', 'ubah')
                                    <a href="{{ route('racikan.edit', $racikan) }}" class="btn btn-sm btn-warning">Ubah</a>
                                @endizin
                                @izin('master.racikan', 'hapus')
                                    <form method="post" action="{{ route('racikan.destroy', $racikan) }}" class="d-inline"
                                        onsubmit="return confirm('Hapus menu racikan ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Belum ada menu racikan.</td>
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
