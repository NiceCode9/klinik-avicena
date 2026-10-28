@extends('layouts.app')

@section('title', 'Catat Pengambilan Spesimen')
@section('page-title', 'Catat Pengambilan Spesimen')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="post" action="{{ route('spesimen.store') }}">
        @csrf
        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Permintaan Lab *</label>
                    <input type="number" name="permintaan_id" value="{{ old('permintaan_id', $permintaanId ?? '') }}" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kode Spesimen (SNOMED)</label>
                    <input type="text" name="kode_spesimen" value="{{ old('kode_spesimen') }}" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Spesimen</label>
                    <input type="text" name="nama_spesimen" value="{{ old('nama_spesimen') }}" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Status Awal</label>
                    <select name="status" class="form-select">
                        <option value="menunggu_diambil" @selected(old('status') === 'menunggu_diambil')>Menunggu Diambil</option>
                        <option value="diambil" @selected(old('status') === 'diambil')>Diambil</option>
                        <option value="diterima" @selected(old('status') === 'diterima')>Diterima</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2">{{ old('catatan') }}</textarea>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('spesimen.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
@endsection