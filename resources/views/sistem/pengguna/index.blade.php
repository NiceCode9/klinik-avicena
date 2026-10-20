@extends('layouts.app')

@section('title', 'Pengguna')
@section('page-title', 'Pengguna')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif
    @if (session('gagal'))
        <div class="alert alert-danger">{{ session('gagal') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Pengguna</span>
            @izin('sistem.pengguna', 'tambah')
                <a href="{{ route('pengguna.create') }}" class="btn btn-sm btn-primary ms-auto">
                    <i class="bi bi-plus-lg"></i> Tambah Pengguna
                </a>
            @endizin
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px">No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Peran</th>
                        <th>Dokter</th>
                        <th>Status</th>
                        <th class="text-end pe-3" style="width:180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $pengguna)
                        <tr>
                            <td class="text-center">{{ $data->firstItem() + $loop->index }}</td>
                            <td>{{ $pengguna->nama }}</td>
                            <td>{{ $pengguna->username }}</td>
                            <td>{{ $pengguna->peran?->nama }}</td>
                            <td>{{ $pengguna->dokter?->nama ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $pengguna->aktif ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $pengguna->aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                @izin('sistem.pengguna', 'ubah')
                                    <a href="{{ route('pengguna.edit', $pengguna) }}" class="btn btn-sm btn-warning">Ubah</a>
                                @endizin
                                @izin('sistem.pengguna', 'hapus')
                                    @if ($pengguna->id !== auth()->id())
                                        <form method="post" action="{{ route('pengguna.destroy', $pengguna) }}" class="d-inline"
                                            onsubmit="return confirm('Hapus pengguna ini?')">
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
                            <td colspan="7" class="text-center text-muted py-3">Belum ada data pengguna.</td>
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
