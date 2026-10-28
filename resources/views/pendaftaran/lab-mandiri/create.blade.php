@extends('layouts.app')

@section('title', 'Pendaftaran Lab Mandiri Baru')
@section('page-title', 'Pendaftaran Lab Mandiri Baru')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('lab-mandiri.store') }}">
        @csrf

        <div class="card mb-3">
            <div class="card-header">Data Pasien (cari NIK atau isi baru)</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">NIK</label>
                        <input type="text" name="nik" value="{{ old('nik') }}" class="form-control" maxlength="16">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap') }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Lahir *</label>
                        <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Jenis Kelamin *</label>
                        <select name="jenis_kelamin" class="form-select">
                            <option value="L" @selected(old('jenis_kelamin') === 'L')>L</option>
                            <option value="P" @selected(old('jenis_kelamin') === 'P')>P</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Gol. Darah</label>
                        <select name="golongan_darah" class="form-select">
                            @foreach (['-', 'A', 'B', 'AB', 'O'] as $g)
                                <option value="{{ $g }}" @selected(old('golongan_darah', '-') === $g)>{{ $g }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="no_telepon" value="{{ old('no_telepon') }}" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2">{{ old('alamat') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Dokter Lab *</div>
            <div class="card-body">
                <select name="dokter_id" id="dokter_id" class="form-select" required>
                    <option value="">-- pilih dokter --</option>
                </select>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Pemeriksaan Lab *</div>
            <div class="card-body">
                @foreach ($pemeriksaan as $p)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="pemeriksaan_lab_ids[]" value="{{ $p->id }}" id="lab_{{ $p->id }}"
                            @checked(in_array((string) $p->id, (array) old('pemeriksaan_lab_ids', [])))>
                        <label class="form-check-label" for="lab_{{ $p->id }}">{{ $p->kode }} - {{ $p->nama }} (Rp {{ number_format($p->tarif, 0, ',', '.') }})</label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('lab-mandiri.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    fetch('{{ route('lab-mandiri.dokter') }}')
        .then(r => r.json())
        .then(list => {
            const sel = document.getElementById('dokter_id');
            list.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = d.nama;
                sel.appendChild(opt);
            });
        });
</script>
@endpush