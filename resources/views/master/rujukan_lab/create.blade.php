@extends('layouts.app')

@section('title', 'Buat Rujukan Lab')
@section('page-title', 'Buat Rujukan Lab')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="get" action="{{ route('rujukan-lab.create') }}" class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label">ID Pemeriksaan</label>
                    <input type="number" name="pemeriksaan_id" class="form-control" value="{{ $pemeriksaan?->id }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Muat</button>
                </div>
            </form>

            @if ($pemeriksaan)
                <p>Pasien: <strong>{{ $pemeriksaan->pasien?->nama_lengkap ?? '-' }}</strong></p>
                <form method="post" action="{{ route('rujukan-lab.store') }}">
                    @csrf
                    <input type="hidden" name="pemeriksaan_id" value="{{ $pemeriksaan->id }}">
                    <div class="mb-3">
                        <label class="form-label">Pemeriksaan Lab (pilih satu atau lebih)</label>
                        <select name="pemeriksaan_lab_ids[]" class="form-select" multiple required style="height:200px">
                            @foreach ($labList as $lab)
                                <option value="{{ $lab->id }}">{{ $lab->nama }} ({{ $lab->kode }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="catatan" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Buat Rujukan</button>
                </form>
            @endif
        </div>
    </div>
@endsection