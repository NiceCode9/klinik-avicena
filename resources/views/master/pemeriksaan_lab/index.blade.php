@extends('layouts.app')

@section('title', 'Pemeriksaan Lab')
@section('page-title', 'Pemeriksaan Lab')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Pemeriksaan Lab</span>
            @izin('master.pemeriksaan_lab', 'tambah')
                <a href="{{ route('pemeriksaan-lab.create') }}" class="btn btn-sm btn-primary ms-auto">
                    <i class="bi bi-plus-lg"></i> Tambah Pemeriksaan
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
                        <th class="text-center" style="width:100px">Parameter</th>
                        <th class="text-end" style="width:130px">Tarif</th>
                        <th style="width:110px">Status</th>
                        <th class="text-end pe-3" style="width:180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $pemeriksaan)
                        <tr>
                            <td class="text-center">{{ $data->firstItem() + $loop->index }}</td>
                            <td><code>{{ $pemeriksaan->kode }}</code></td>
                            <td>{{ $pemeriksaan->nama }}</td>
                            <td class="text-center">{{ $pemeriksaan->parameter_lab_count }}</td>
                            <td class="text-end">{{ number_format($pemeriksaan->tarif, 0, ',', '.') }}</td>
                            <td>
                                @if ($pemeriksaan->aktif)
                                    <span class="badge text-bg-success">Aktif</span>
                                @else
                                    <span class="badge text-bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @izin('master.pemeriksaan_lab', 'ubah')
                                    <a href="{{ route('pemeriksaan-lab.edit', $pemeriksaan) }}"
                                        class="btn btn-sm btn-warning">Ubah</a>
                                @endizin
                                @izin('master.pemeriksaan_lab', 'hapus')
                                    <form method="post" action="{{ route('pemeriksaan-lab.destroy', $pemeriksaan) }}"
                                        class="d-inline" onsubmit="return confirm('Hapus pemeriksaan ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">Belum ada data pemeriksaan lab.</td>
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
