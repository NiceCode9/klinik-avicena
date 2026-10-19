@extends('layouts.app')

@section('title', 'Tindakan Medis')
@section('page-title', 'Tindakan Medis')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Tindakan Medis</span>
            @izin('master.tindakan', 'tambah')
                <a href="{{ route('tindakan.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> Tambah Tindakan
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
                        <th style="width:110px">Poli</th>
                        <th class="text-end" style="width:140px">Tarif</th>
                        <th style="width:110px">Status</th>
                        <th class="text-end pe-3" style="width:180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $tindakan)
                        <tr>
                            <td class="text-center">{{ $data->firstItem() + $loop->index }}</td>
                            <td><code>{{ $tindakan->kode }}</code></td>
                            <td>{{ $tindakan->nama }}</td>
                            <td>{{ $tindakan->poli?->kode ?? '-' }}</td>
                            <td class="text-end">{{ number_format($tindakan->tarif, 0, ',', '.') }}</td>
                            <td>
                                @if ($tindakan->aktif)
                                    <span class="badge text-bg-success">Aktif</span>
                                @else
                                    <span class="badge text-bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @izin('master.tindakan', 'ubah')
                                    <a href="{{ route('tindakan.edit', $tindakan) }}"
                                        class="btn btn-sm btn-warning">Ubah</a>
                                @endizin
                                @izin('master.tindakan', 'hapus')
                                    <form method="post" action="{{ route('tindakan.destroy', $tindakan) }}"
                                        class="d-inline" onsubmit="return confirm('Hapus tindakan ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">Belum ada data tindakan.</td>
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
