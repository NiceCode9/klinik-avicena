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
    <label for="nama" class="form-label">Nama Dokter</label>
    <input type="text" id="nama" name="nama" maxlength="150"
        class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $dokter->nama ?? '') }}">
    @error('nama')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="spesialisasi" class="form-label">Spesialisasi (opsional)</label>
        <input type="text" id="spesialisasi" name="spesialisasi" maxlength="100"
            class="form-control @error('spesialisasi') is-invalid @enderror"
            value="{{ old('spesialisasi', $dokter->spesialisasi ?? '') }}">
        @error('spesialisasi')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="poli_id" class="form-label">Poli (opsional)</label>
        <select id="poli_id" name="poli_id" class="form-select @error('poli_id') is-invalid @enderror">
            <option value="">- Tidak terikat poli -</option>
            @foreach ($daftarPoli as $poli)
                <option value="{{ $poli->id }}" @selected((int) old('poli_id', $dokter->poli_id ?? null) === $poli->id)>
                    {{ $poli->kode }} - {{ $poli->nama }}
                </option>
            @endforeach
        </select>
        @error('poli_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="no_sip" class="form-label">No. SIP (opsional)</label>
        <input type="text" id="no_sip" name="no_sip" maxlength="50"
            class="form-control @error('no_sip') is-invalid @enderror"
            value="{{ old('no_sip', $dokter->no_sip ?? '') }}">
        @error('no_sip')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="nik" class="form-label">NIK (opsional)</label>
        <input type="text" id="nik" name="nik" maxlength="16" inputmode="numeric"
            class="form-control @error('nik') is-invalid @enderror" value="{{ old('nik', $dokter->nik ?? '') }}">
        @error('nik')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">16 digit angka; tersimpan terenkripsi.</div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="tarif_konsultasi" class="form-label">Tarif Konsultasi (Rp)</label>
        <input type="number" id="tarif_konsultasi" name="tarif_konsultasi" min="0" step="0.01"
            class="form-control @error('tarif_konsultasi') is-invalid @enderror"
            value="{{ old('tarif_konsultasi', $dokter->tarif_konsultasi ?? 0) }}">
        @error('tarif_konsultasi')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="ihs_practitioner_id" class="form-label">ID IHS Practitioner (opsional)</label>
        <input type="text" id="ihs_practitioner_id" name="ihs_practitioner_id" maxlength="64"
            class="form-control @error('ihs_practitioner_id') is-invalid @enderror"
            value="{{ old('ihs_practitioner_id', $dokter->ihs_practitioner_id ?? '') }}">
        @error('ihs_practitioner_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Diisi nanti saat integrasi SATUSEHAT.</div>
    </div>
</div>

<div class="form-check mb-3">
    <input type="hidden" name="aktif" value="0">
    <input type="checkbox" id="aktif" name="aktif" value="1" class="form-check-input"
        @checked(old('aktif', $dokter->aktif ?? true))>
    <label for="aktif" class="form-check-label">Aktif</label>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('dokter.index') }}" class="btn btn-secondary">Batal</a>
</div>
