@extends('layouts.app')

@section('title', 'Satuan')
@section('page-title', 'Satuan')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Satuan</span>
            @izin('master.satuan', 'tambah')
                <a href="{{ route('satuan.create') }}" class="btn btn-sm btn-primary ms-auto">
                    <i class="bi bi-plus-lg"></i> Tambah Satuan
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
                    @forelse ($data as $satuan)
                        <tr>
                            <td class="text-center">{{ $data->firstItem() + $loop->index }}</td>
                            <td>{{ $satuan->nama }}</td>
                            <td class="text-end pe-3">
                                @izin('master.satuan', 'ubah')
                                    <a href="{{ route('satuan.edit', $satuan) }}" class="btn btn-sm btn-warning">Ubah</a>
                                @endizin
                                @izin('master.satuan', 'hapus')
                                    <form method="post" action="{{ route('satuan.destroy', $satuan) }}" class="d-inline"
                                        onsubmit="return confirm('Hapus satuan ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">Belum ada data satuan.</td>
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
