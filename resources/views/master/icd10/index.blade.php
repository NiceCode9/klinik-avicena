@extends('layouts.app')

@section('title', 'ICD-10')
@section('page-title', 'ICD-10')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    @if (session('gagal'))
        <div class="alert alert-danger">{{ session('gagal') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                Daftar Kode ICD-10
                <span class="text-muted small">({{ number_format($data->total(), 0, ',', '.') }} kode)</span>
            </span>
            @izin('master.icd10', 'tambah')
                <div class="ms-auto d-flex gap-2">
                    <a href="{{ route('icd10.impor') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-upload"></i> Impor CSV
                    </a>
                    <a href="{{ route('icd10.create') }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg"></i> Tambah Kode
                    </a>
                </div>
            @endizin
        </div>
        <div class="card-body">
            <form method="get" action="{{ route('icd10.index') }}" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="text" name="cari" value="{{ $cari }}" class="form-control"
                        placeholder="Cari kode atau nama diagnosis">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-secondary">Cari</button>
                </div>
                @if ($cari !== '')
                    <div class="col-auto">
                        <a href="{{ route('icd10.index') }}" class="btn btn-link">Reset</a>
                    </div>
                @endif
            </form>

            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px">No</th>
                        <th style="width:120px">Kode</th>
                        <th>Nama Diagnosis</th>
                        <th class="text-end pe-3" style="width:180px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $icd10)
                        <tr>
                            <td class="text-center">{{ $data->firstItem() + $loop->index }}</td>
                            <td><code>{{ $icd10->kode }}</code></td>
                            <td>{{ $icd10->nama_diagnosis }}</td>
                            <td class="text-end pe-3">
                                @izin('master.icd10', 'ubah')
                                    <a href="{{ route('icd10.edit', $icd10) }}" class="btn btn-sm btn-warning">Ubah</a>
                                @endizin
                                @izin('master.icd10', 'hapus')
                                    <form method="post" action="{{ route('icd10.destroy', $icd10) }}" class="d-inline"
                                        onsubmit="return confirm('Hapus kode ini?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Tidak ada kode ICD-10 yang cocok.</td>
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
