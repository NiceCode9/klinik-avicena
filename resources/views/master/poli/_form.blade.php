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
    <label for="kode" class="form-label">Kode Poli</label>
    <input type="text" id="kode" name="kode" maxlength="5"
        class="form-control @error('kode') is-invalid @enderror" value="{{ old('kode', $poli->kode ?? '') }}">
    @error('kode')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="form-text">Singkatan untuk nomor antrean, maksimal 5 karakter (mis. INT, GIG, SRF, LAB).</div>
</div>

<div class="mb-3">
    <label for="nama" class="form-label">Nama Poli</label>
    <input type="text" id="nama" name="nama" maxlength="100"
        class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $poli->nama ?? '') }}">
    @error('nama')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="ihs_location_id" class="form-label">ID IHS Location (opsional)</label>
    <input type="text" id="ihs_location_id" name="ihs_location_id" maxlength="64"
        class="form-control @error('ihs_location_id') is-invalid @enderror"
        value="{{ old('ihs_location_id', $poli->ihs_location_id ?? '') }}">
    @error('ihs_location_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="form-text">Diisi nanti saat integrasi SATUSEHAT.</div>
</div>

<div class="form-check mb-3">
    <input type="hidden" name="aktif" value="0">
    <input type="checkbox" id="aktif" name="aktif" value="1" class="form-check-input"
        @checked(old('aktif', $poli->aktif ?? true))>
    <label for="aktif" class="form-check-label">Aktif</label>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('poli.index') }}" class="btn btn-secondary">Batal</a>
</div>
