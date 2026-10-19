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
    <label for="nama" class="form-label">Nama Supplier / PBF</label>
    <input type="text" id="nama" name="nama" maxlength="150"
        class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $supplier->nama ?? '') }}">
    @error('nama')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="alamat" class="form-label">Alamat (opsional)</label>
    <textarea id="alamat" name="alamat" rows="2"
        class="form-control @error('alamat') is-invalid @enderror">{{ old('alamat', $supplier->alamat ?? '') }}</textarea>
    @error('alamat')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="no_telepon" class="form-label">No. Telepon (opsional)</label>
        <input type="text" id="no_telepon" name="no_telepon" maxlength="20"
            class="form-control @error('no_telepon') is-invalid @enderror"
            value="{{ old('no_telepon', $supplier->no_telepon ?? '') }}">
        @error('no_telepon')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="nama_kontak" class="form-label">Nama Kontak (opsional)</label>
        <input type="text" id="nama_kontak" name="nama_kontak" maxlength="100"
            class="form-control @error('nama_kontak') is-invalid @enderror"
            value="{{ old('nama_kontak', $supplier->nama_kontak ?? '') }}">
        @error('nama_kontak')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label for="no_izin_pbf" class="form-label">No. Izin PBF (opsional)</label>
    <input type="text" id="no_izin_pbf" name="no_izin_pbf" maxlength="50"
        class="form-control @error('no_izin_pbf') is-invalid @enderror"
        value="{{ old('no_izin_pbf', $supplier->no_izin_pbf ?? '') }}">
    @error('no_izin_pbf')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-check mb-3">
    <input type="hidden" name="aktif" value="0">
    <input type="checkbox" id="aktif" name="aktif" value="1" class="form-check-input"
        @checked(old('aktif', $supplier->aktif ?? true))>
    <label for="aktif" class="form-check-label">Aktif</label>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('supplier.index') }}" class="btn btn-secondary">Batal</a>
</div>
