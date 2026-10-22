@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mb-3">
    <label for="nama" class="form-label">Nama Satuan</label>
    <input type="text" id="nama" name="nama" maxlength="50"
        class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $satuan->nama ?? '') }}">
    @error('nama')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="kode_ucum" class="form-label">Kode UCUM (opsional)</label>
    <input type="text" id="kode_ucum" name="kode_ucum" maxlength="30"
        class="form-control @error('kode_ucum') is-invalid @enderror" value="{{ old('kode_ucum', $satuan->kode_ucum ?? '') }}">
    @error('kode_ucum')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="form-text">Satuan baku FHIR, mis. mg, mL, atau {tablet}.</div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('satuan.index') }}" class="btn btn-secondary">Batal</a>
</div>
