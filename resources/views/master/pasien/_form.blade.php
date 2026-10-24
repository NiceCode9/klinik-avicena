@php $pasien = $pasien ?? null; @endphp
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
    <div class="col-md-6 mb-3">
        <label for="nik" class="form-label">NIK</label>
        <input type="text" id="nik" name="nik" maxlength="16" inputmode="numeric"
            class="form-control @error('nik') is-invalid @enderror" value="{{ old('nik', $pasien?->nik ?? '') }}">
        @error('nik')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
        <input type="text" id="nama_lengkap" name="nama_lengkap" maxlength="150"
            class="form-control @error('nama_lengkap') is-invalid @enderror"
            value="{{ old('nama_lengkap', $pasien?->nama_lengkap ?? '') }}">
        @error('nama_lengkap')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="tempat_lahir" class="form-label">Tempat Lahir</label>
        <input type="text" id="tempat_lahir" name="tempat_lahir" maxlength="100"
            class="form-control @error('tempat_lahir') is-invalid @enderror"
            value="{{ old('tempat_lahir', $pasien?->tempat_lahir ?? '') }}">
        @error('tempat_lahir')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
        <input type="date" id="tanggal_lahir" name="tanggal_lahir"
            class="form-control @error('tanggal_lahir') is-invalid @enderror"
            value="{{ old('tanggal_lahir', $pasien?->tanggal_lahir?->format('Y-m-d') ?? '') }}">
        @error('tanggal_lahir')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
        <select id="jenis_kelamin" name="jenis_kelamin" class="form-select @error('jenis_kelamin') is-invalid @enderror">
            <option value="">- Pilih -</option>
            <option value="L" @selected(old('jenis_kelamin', $pasien?->jenis_kelamin ?? '') === 'L')>Laki-laki</option>
            <option value="P" @selected(old('jenis_kelamin', $pasien?->jenis_kelamin ?? '') === 'P')>Perempuan</option>
        </select>
        @error('jenis_kelamin')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="golongan_darah" class="form-label">Golongan Darah</label>
        <select id="golongan_darah" name="golongan_darah" class="form-select @error('golongan_darah') is-invalid @enderror">
            <option value="">- Pilih -</option>
            @foreach (['A', 'B', 'AB', 'O', '-'] as $gd)
                <option value="{{ $gd }}" @selected(old('golongan_darah', $pasien?->golongan_darah ?? '') === $gd)>{{ $gd }}</option>
            @endforeach
        </select>
        @error('golongan_darah')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="no_telepon" class="form-label">No. Telepon</label>
        <input type="text" id="no_telepon" name="no_telepon" maxlength="20"
            class="form-control @error('no_telepon') is-invalid @enderror"
            value="{{ old('no_telepon', $pasien?->no_telepon ?? '') }}">
        @error('no_telepon')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="nama_wali" class="form-label">Nama Wali</label>
        <input type="text" id="nama_wali" name="nama_wali" maxlength="150"
            class="form-control @error('nama_wali') is-invalid @enderror"
            value="{{ old('nama_wali', $pasien?->nama_wali ?? '') }}">
        @error('nama_wali')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label for="alamat" class="form-label">Alamat</label>
    <textarea id="alamat" name="alamat" rows="2" maxlength="255"
        class="form-control @error('alamat') is-invalid @enderror">{{ old('alamat', $pasien?->alamat ?? '') }}</textarea>
    @error('alamat')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('pasien.index') }}" class="btn btn-secondary">Batal</a>
</div>