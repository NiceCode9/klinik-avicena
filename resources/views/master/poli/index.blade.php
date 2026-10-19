@extends('layouts.app')

@section('title', 'Poli')
@section('page-title', 'Poli')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Poli</span>
            @izin('master.poli', 'tambah')
                <a href="{{ route('poli.create') }}" class="btn btn-sm btn-primary ms-auto">
                    <i class="bi bi-plus-lg"></i> Tambah Poli
                </a>
            @endizin
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px">No</th>
                        <th style="width:90px">Kode</th>
                        <th>Nama</th>
                        <th style="width:110px">Status</th>
                        <th class="text-end pe-3" style="width:180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $poli)
                        <tr>
                            <td class="text-center">{{ $data->firstItem() + $loop->index }}</td>
                            <td><code>{{ $poli->kode }}</code></td>
                            <td>{{ $poli->nama }}</td>
                            <td>
                                @if ($poli->aktif)
                                    <span class="badge text-bg-success">Aktif</span>
                                @else
                                    <span class="badge text-bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @izin('master.poli', 'ubah')
                                    <a href="{{ route('poli.edit', $poli) }}" class="btn btn-sm btn-warning">Ubah</a>
                                @endizin
                                @izin('master.poli', 'hapus')
                                    <form method="post" action="{{ route('poli.destroy', $poli) }}" class="d-inline"
                                        onsubmit="return confirm('Hapus poli ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Belum ada data poli.</td>
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
