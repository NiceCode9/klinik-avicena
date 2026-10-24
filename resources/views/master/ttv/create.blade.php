@extends('layouts.app')

@section('title', 'Input TTV')
@section('page-title', 'Input TTV')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <p class="mb-3">
                Pasien: <strong>{{ $kunjungan->pasien?->nama_lengkap ?? '-' }}</strong>
                ({{ $kunjungan->no_kunjungan }})
            </p>
            <form method="post" action="{{ route('ttv.store', $kunjungan) }}">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="poli_id" class="form-label">Poli</label>
                        <select id="poli_id" name="poli_id" class="form-select" required>
                            <option value="">- Pilih Poli -</option>
                            @foreach ($poli as $p)
                                <option value="{{ $p->id }}" @selected(old('poli_id') == $p->id)>{{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">TD Sistolik</label>
                        <input type="number" name="tekanan_darah_sistolik" class="form-control" required value="{{ old('tekanan_darah_sistolik') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">TD Diastolik</label>
                        <input type="number" name="tekanan_darah_diastolik" class="form-control" required value="{{ old('tekanan_darah_diastolik') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Nadi</label>
                        <input type="number" name="nadi" class="form-control" required value="{{ old('nadi') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Suhu</label>
                        <input type="number" step="0.1" name="suhu" class="form-control" required value="{{ old('suhu') }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Berat Badan (kg)</label>
                        <input type="number" step="0.1" name="berat_badan" class="form-control" required value="{{ old('berat_badan') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tinggi Badan (cm)</label>
                        <input type="number" step="0.1" name="tinggi_badan" class="form-control" value="{{ old('tinggi_badan') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Saturasi Oksigen (%)</label>
                        <input type="number" name="saturasi_oksigen" class="form-control" value="{{ old('saturasi_oksigen') }}">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Simpan TTV</button>
                <a href="{{ route('ttv.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
@endsection