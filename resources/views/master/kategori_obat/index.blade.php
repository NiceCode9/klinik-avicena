@extends('layouts.app')

@section('title', 'Kategori Obat')
@section('page-title', 'Kategori Obat')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Kategori Obat</span>
            @izin('master.kategori_obat', 'tambah')
                <a href="{{ route('kategori-obat.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> Tambah Kategori Obat
                </a>
            @endizin
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px">No</th>
                        <th>Nama</th>
                        <th class="text-end pe-3" style="width:180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $kategori)
                        <tr>
                            <td class="text-center">{{ $data->firstItem() + $loop->index }}</td>
                            <td>{{ $kategori->nama }}</td>
                            <td class="text-end pe-3">
                                @izin('master.kategori_obat', 'ubah')
                                    <a href="{{ route('kategori-obat.edit', $kategori) }}"
                                        class="btn btn-sm btn-warning">Ubah</a>
                                @endizin
                                @izin('master.kategori_obat', 'hapus')
                                    <form method="post" action="{{ route('kategori-obat.destroy', $kategori) }}"
                                        class="d-inline" onsubmit="return confirm('Hapus kategori obat ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">Belum ada data kategori obat.</td>
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
