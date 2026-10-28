@extends('layouts.app')

@section('title', 'Ubah Status Spesimen')
@section('page-title', 'Ubah Status Spesimen')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="post" action="{{ route('spesimen.update', $spesimen) }}">
        @csrf
        @method('PATCH')
        <div class="card mb-3">
            <div class="card-body">
                <p><strong>{{ $spesimen->nama_spesimen }}</strong> ({{ $spesimen->kode_spesimen }})</p>
                <div class="mb-3">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-select">
                        @foreach (['menunggu_diambil','diambil','diterima','ditolak'] as $s)
                            <option value="{{ $s }}" @selected($spesimen->status === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan (wajib jika ditolak)</label>
                    <textarea name="catatan" class="form-control" rows="2">{{ $spesimen->catatan }}</textarea>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('spesimen.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
@endsection