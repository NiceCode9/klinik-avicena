@extends('layouts.app')

@section('title', 'Tenaga Kesehatan')
@section('page-title', 'Tenaga Kesehatan')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Tenaga Kesehatan</span>
            @izin('master.nakes', 'tambah')
                <a href="{{ route('nakes.create') }}" class="btn btn-sm btn-primary ms-auto">
                    <i class="bi bi-plus-lg"></i> Tambah Nakes
                </a>
            @endizin
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px">No</th>
                        <th>Nama</th>
                        <th style="width:120px">Jenis</th>
                        <th style="width:150px">No. STR</th>
                        <th style="width:170px">IHS Practitioner</th>
                        <th style="width:150px">Akun</th>
                        <th style="width:110px">Status</th>
                        <th class="text-end pe-3" style="width:180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $nakes)
                        <tr>
                            <td class="text-center">{{ $data->firstItem() + $loop->index }}</td>
                            <td>{{ $nakes->nama }}</td>
                            <td>{{ \App\Models\Master\Nakes::JENIS[$nakes->jenis] ?? $nakes->jenis }}</td>
                            <td>{{ $nakes->no_str ?? '-' }}</td>
                            <td>{{ $nakes->ihs_practitioner_id ?? '-' }}</td>
                            <td>{{ $nakes->pengguna?->nama ?? '-' }}</td>
                            <td>
                                @if ($nakes->aktif)
                                    <span class="badge text-bg-success">Aktif</span>
                                @else
                                    <span class="badge text-bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @izin('master.nakes', 'ubah')
                                    <a href="{{ route('nakes.edit', $nakes) }}" class="btn btn-sm btn-warning">Ubah</a>
                                @endizin
                                @izin('master.nakes', 'hapus')
                                    <form method="post" action="{{ route('nakes.destroy', $nakes) }}" class="d-inline"
                                        onsubmit="return confirm('Hapus tenaga kesehatan ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">Belum ada data tenaga kesehatan.</td>
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
