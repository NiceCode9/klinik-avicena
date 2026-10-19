@extends('layouts.app')

@section('title', 'Supplier')
@section('page-title', 'Supplier / PBF')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Supplier / PBF</span>
            @izin('master.supplier', 'tambah')
                <a href="{{ route('supplier.create') }}" class="btn btn-sm btn-primary ms-auto">
                    <i class="bi bi-plus-lg"></i> Tambah Supplier
                </a>
            @endizin
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px">No</th>
                        <th>Nama</th>
                        <th style="width:150px">No. Telepon</th>
                        <th style="width:150px">Kontak</th>
                        <th style="width:110px">Status</th>
                        <th class="text-end pe-3" style="width:180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $supplier)
                        <tr>
                            <td class="text-center">{{ $data->firstItem() + $loop->index }}</td>
                            <td>
                                {{ $supplier->nama }}
                                @if ($supplier->no_izin_pbf)
                                    <div class="small text-muted">No. Izin PBF: {{ $supplier->no_izin_pbf }}</div>
                                @endif
                            </td>
                            <td>{{ $supplier->no_telepon ?? '-' }}</td>
                            <td>{{ $supplier->nama_kontak ?? '-' }}</td>
                            <td>
                                @if ($supplier->aktif)
                                    <span class="badge text-bg-success">Aktif</span>
                                @else
                                    <span class="badge text-bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @izin('master.supplier', 'ubah')
                                    <a href="{{ route('supplier.edit', $supplier) }}"
                                        class="btn btn-sm btn-warning">Ubah</a>
                                @endizin
                                @izin('master.supplier', 'hapus')
                                    <form method="post" action="{{ route('supplier.destroy', $supplier) }}"
                                        class="d-inline" onsubmit="return confirm('Hapus supplier ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Belum ada data supplier.</td>
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
