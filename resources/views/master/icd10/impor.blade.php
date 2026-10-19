@extends('layouts.app')

@section('title', 'Impor ICD-10')
@section('page-title', 'Impor ICD-10')

@section('content')
    @if ($ringkasan = session('ringkasan'))
        <div class="alert alert-info">
            Impor selesai: <strong>{{ $ringkasan['ditambah'] }}</strong> ditambah,
            <strong>{{ $ringkasan['diperbarui'] }}</strong> diperbarui,
            <strong>{{ $ringkasan['dilewati'] }}</strong> dilewati.
        </div>

        @if (count($ringkasan['rincianDilewati']) > 0)
            <div class="card mb-3">
                <div class="card-header">Baris yang Dilewati</div>
                <div class="card-body p-0">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:100px">Baris</th>
                                <th>Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ringkasan['rincianDilewati'] as $rincian)
                                <tr>
                                    <td>{{ $rincian['baris'] }}</td>
                                    <td>{{ $rincian['alasan'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($ringkasan['dilewati'] > count($ringkasan['rincianDilewati']))
                    <div class="card-footer text-muted small">
                        Menampilkan {{ count($ringkasan['rincianDilewati']) }} dari {{ $ringkasan['dilewati'] }} baris yang dilewati.
                    </div>
                @endif
            </div>
        @endif
    @endif

    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('icd10.impor.simpan') }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="berkas" class="form-label">File CSV</label>
                    <input type="file" id="berkas" name="berkas" accept=".csv,.txt"
                        class="form-control @error('berkas') is-invalid @enderror">
                    @error('berkas')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">
                        Kolom pertama kode ICD-10, kolom kedua nama diagnosis. Pemisah koma, titik-koma, atau tab;
                        baris header dan baris kosong dilewati otomatis. Kode yang sudah ada akan diperbarui.
                        Maksimal 5 MB.
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Impor</button>
                    <a href="{{ route('icd10.index') }}" class="btn btn-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
@endsection
