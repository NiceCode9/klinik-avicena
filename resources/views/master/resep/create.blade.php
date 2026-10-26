@extends('layouts.app')

@section('title', 'Buat Resep')
@section('page-title', 'Buat Resep')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="get" action="{{ route('resep.create') }}" class="row g-2 mb-3">
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
                <form method="post" action="{{ route('resep.store') }}">
                    @csrf
                    <input type="hidden" name="pemeriksaan_id" value="{{ $pemeriksaan->id }}">
                    <div class="mb-3">
                        <label class="form-label">Items (JSON)</label>
                        <textarea name="items" class="form-control" rows="8">[]</textarea>
                        <div class="form-text">Contoh: [{"jenis":"reguler","obat_id":1,"jumlah":10,"dosis":1,"satuan_dosis":"tablet","frekuensi":3,"periode":1,"satuan_periode":"hari","kode_rute":"PO","nama_rute":"Per oral","lama_hari":5}]</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Buat Resep</button>
                </form>
            @endif
        </div>
    </div>
@endsection