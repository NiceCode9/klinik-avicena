@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-md-3 mb-3">
        <label for="kode" class="form-label">Kode ICD-10</label>
        <input type="text" id="kode" name="kode" maxlength="10" placeholder="mis. A00"
            class="form-control @error('kode') is-invalid @enderror" value="{{ old('kode', $icd10->kode ?? '') }}">
        @error('kode')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-9 mb-3">
        <label for="nama_diagnosis" class="form-label">Nama Diagnosis</label>
        <input type="text" id="nama_diagnosis" name="nama_diagnosis" maxlength="255"
            class="form-control @error('nama_diagnosis') is-invalid @enderror"
            value="{{ old('nama_diagnosis', $icd10->nama_diagnosis ?? '') }}">
        @error('nama_diagnosis')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('icd10.index') }}" class="btn btn-secondary">Batal</a>
</div>
