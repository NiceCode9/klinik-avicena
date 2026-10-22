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
    <div class="col-md-8 mb-3">
        <label for="nama" class="form-label">Nama</label>
        <input type="text" id="nama" name="nama" maxlength="150"
            class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $nakes->nama ?? '') }}">
        @error('nama')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="jenis" class="form-label">Jenis</label>
        <select id="jenis" name="jenis" class="form-select @error('jenis') is-invalid @enderror">
            @foreach (\App\Models\Master\Nakes::JENIS as $nilai => $label)
                <option value="{{ $nilai }}" @selected(old('jenis', $nakes->jenis ?? 'perawat') === $nilai)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('jenis')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="nik" class="form-label">NIK (opsional)</label>
        <input type="text" id="nik" name="nik" maxlength="16" inputmode="numeric"
            class="form-control @error('nik') is-invalid @enderror" value="{{ old('nik', $nakes->nik ?? '') }}">
        @error('nik')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">16 digit angka; tersimpan terenkripsi dan dipakai mencari IHS Practitioner.</div>
    </div>

    <div class="col-md-6 mb-3">
        <label for="no_str" class="form-label">No. STR (opsional)</label>
        <input type="text" id="no_str" name="no_str" maxlength="50"
            class="form-control @error('no_str') is-invalid @enderror" value="{{ old('no_str', $nakes->no_str ?? '') }}">
        @error('no_str')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="ihs_practitioner_id" class="form-label">ID IHS Practitioner (opsional)</label>
        <input type="text" id="ihs_practitioner_id" name="ihs_practitioner_id" maxlength="64"
            class="form-control @error('ihs_practitioner_id') is-invalid @enderror"
            value="{{ old('ihs_practitioner_id', $nakes->ihs_practitioner_id ?? '') }}">
        @error('ihs_practitioner_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Diisi nanti saat sinkron IHS SATUSEHAT.</div>
    </div>

    <div class="col-md-6 mb-3">
        <label for="pengguna_id" class="form-label">Akun Login (opsional)</label>
        <select id="pengguna_id" name="pengguna_id" class="form-select @error('pengguna_id') is-invalid @enderror">
            <option value="">- Tanpa akun -</option>
            @foreach ($daftarPengguna as $pengguna)
                <option value="{{ $pengguna->id }}"
                    @selected((int) old('pengguna_id', $nakes->pengguna_id ?? null) === $pengguna->id)>
                    {{ $pengguna->nama }} ({{ $pengguna->username }})
                </option>
            @endforeach
        </select>
        @error('pengguna_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Satu akun hanya boleh terhubung ke satu tenaga kesehatan.</div>
    </div>
</div>

<div class="form-check mb-3">
    <input type="hidden" name="aktif" value="0">
    <input type="checkbox" id="aktif" name="aktif" value="1" class="form-check-input"
        @checked(old('aktif', $nakes->aktif ?? true))>
    <label for="aktif" class="form-check-label">Aktif</label>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('nakes.index') }}" class="btn btn-secondary">Batal</a>
</div>
