@extends('layouts.app')

@section('title', 'Peran')
@section('page-title', 'Peran')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif
    @if (session('gagal'))
        <div class="alert alert-danger">{{ session('gagal') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Peran</span>
            @izin('sistem.peran', 'tambah')
                <a href="{{ route('peran.create') }}" class="btn btn-sm btn-primary ms-auto">
                    <i class="bi bi-plus-lg"></i> Tambah Peran
                </a>
            @endizin
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px">No</th>
                        <th>Nama</th>
                        <th>Keterangan</th>
                        <th class="text-center" style="width:140px">Jumlah Pengguna</th>
                        <th class="text-end pe-3" style="width:180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $peran)
                        <tr>
                            <td class="text-center">{{ $data->firstItem() + $loop->index }}</td>
                            <td>{{ $peran->nama }}</td>
                            <td>{{ $peran->keterangan ?? '—' }}</td>
                            <td class="text-center">{{ $peran->pengguna_count }}</td>
                            <td class="text-end pe-3">
                                @izin('sistem.peran', 'ubah')
                                    <a href="{{ route('peran.edit', $peran) }}" class="btn btn-sm btn-warning">Ubah</a>
                                @endizin
                                @izin('sistem.peran', 'hapus')
                                    @if ($peran->nama !== 'admin')
                                        <form method="post" action="{{ route('peran.destroy', $peran) }}" class="d-inline"
                                            onsubmit="return confirm('Hapus peran ini?')">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                    @endif
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Belum ada data peran.</td>
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
