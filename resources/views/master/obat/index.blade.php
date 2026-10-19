@extends('layouts.app')

@section('title', 'Obat')
@section('page-title', 'Obat')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Obat</span>
            @izin('master.obat', 'tambah')
                <a href="{{ route('obat.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> Tambah Obat
                </a>
            @endizin
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px">No</th>
                        <th style="width:120px">Kode</th>
                        <th>Nama</th>
                        <th style="width:100px">Satuan</th>
                        <th style="width:130px">Golongan</th>
                        <th class="text-end" style="width:120px">Harga Jual</th>
                        <th style="width:110px">Status</th>
                        <th class="text-end pe-3" style="width:180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $obat)
                        <tr>
                            <td class="text-center">{{ $data->firstItem() + $loop->index }}</td>
                            <td><code>{{ $obat->kode }}</code></td>
                            <td>
                                {{ $obat->nama }}
                                @if ($obat->kategoriObat)
                                    <div class="small text-muted">{{ $obat->kategoriObat->nama }}</div>
                                @endif
                            </td>
                            <td>{{ $obat->satuan?->nama ?? '-' }}</td>
                            <td>{{ \App\Models\Master\Obat::GOLONGAN[$obat->golongan] ?? $obat->golongan }}</td>
                            <td class="text-end">{{ number_format($obat->harga_jual, 0, ',', '.') }}</td>
                            <td>
                                @if ($obat->aktif)
                                    <span class="badge text-bg-success">Aktif</span>
                                @else
                                    <span class="badge text-bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @izin('master.obat', 'ubah')
                                    <a href="{{ route('obat.edit', $obat) }}" class="btn btn-sm btn-warning">Ubah</a>
                                @endizin
                                @izin('master.obat', 'hapus')
                                    <form method="post" action="{{ route('obat.destroy', $obat) }}" class="d-inline"
                                        onsubmit="return confirm('Hapus obat ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">Belum ada data obat.</td>
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
