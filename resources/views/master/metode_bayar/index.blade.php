@extends('layouts.app')

@section('title', 'Metode Bayar')
@section('page-title', 'Metode Bayar')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Metode Bayar</span>
            @izin('master.metode_bayar', 'tambah')
                <a href="{{ route('metode-bayar.create') }}" class="btn btn-sm btn-primary ms-auto">
                    <i class="bi bi-plus-lg"></i> Tambah Metode Bayar
                </a>
            @endizin
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px">No</th>
                        <th>Nama</th>
                        <th style="width:110px">Status</th>
                        <th class="text-end pe-3" style="width:180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $metode)
                        <tr>
                            <td class="text-center">{{ $data->firstItem() + $loop->index }}</td>
                            <td>{{ $metode->nama }}</td>
                            <td>
                                @if ($metode->aktif)
                                    <span class="badge text-bg-success">Aktif</span>
                                @else
                                    <span class="badge text-bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @izin('master.metode_bayar', 'ubah')
                                    <a href="{{ route('metode-bayar.edit', $metode) }}"
                                        class="btn btn-sm btn-warning">Ubah</a>
                                @endizin
                                @izin('master.metode_bayar', 'hapus')
                                    <form method="post" action="{{ route('metode-bayar.destroy', $metode) }}" class="d-inline"
                                        onsubmit="return confirm('Hapus metode bayar ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Belum ada data metode bayar.</td>
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
