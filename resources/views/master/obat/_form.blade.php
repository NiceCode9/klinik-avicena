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
    <div class="col-md-4 mb-3">
        <label for="kode" class="form-label">Kode</label>
        <input type="text" id="kode" name="kode" maxlength="30"
            class="form-control @error('kode') is-invalid @enderror" value="{{ old('kode', $obat->kode ?? '') }}">
        @error('kode')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-8 mb-3">
        <label for="nama" class="form-label">Nama Obat</label>
        <input type="text" id="nama" name="nama" maxlength="200"
            class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $obat->nama ?? '') }}">
        @error('nama')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="kategori_obat_id" class="form-label">Kategori (opsional)</label>
        <select id="kategori_obat_id" name="kategori_obat_id"
            class="form-select @error('kategori_obat_id') is-invalid @enderror">
            <option value="">- Tanpa kategori -</option>
            @foreach ($daftarKategori as $kategori)
                <option value="{{ $kategori->id }}"
                    @selected((int) old('kategori_obat_id', $obat->kategori_obat_id ?? null) === $kategori->id)>
                    {{ $kategori->nama }}
                </option>
            @endforeach
        </select>
        @error('kategori_obat_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="satuan_id" class="form-label">Satuan</label>
        <select id="satuan_id" name="satuan_id" class="form-select @error('satuan_id') is-invalid @enderror">
            <option value="">- Pilih satuan -</option>
            @foreach ($daftarSatuan as $satuan)
                <option value="{{ $satuan->id }}" @selected((int) old('satuan_id', $obat->satuan_id ?? null) === $satuan->id)>
                    {{ $satuan->nama }}
                </option>
            @endforeach
        </select>
        @error('satuan_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="golongan" class="form-label">Golongan</label>
        <select id="golongan" name="golongan" class="form-select @error('golongan') is-invalid @enderror">
            @foreach (\App\Models\Master\Obat::GOLONGAN as $nilai => $label)
                <option value="{{ $nilai }}" @selected(old('golongan', $obat->golongan ?? 'bebas') === $nilai)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('golongan')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="stok_minimum" class="form-label">Stok Minimum</label>
        <input type="number" id="stok_minimum" name="stok_minimum" min="0" step="0.01"
            class="form-control @error('stok_minimum') is-invalid @enderror"
            value="{{ old('stok_minimum', $obat->stok_minimum ?? 0) }}">
        @error('stok_minimum')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="harga_jual" class="form-label">Harga Jual (Rp)</label>
        <input type="number" id="harga_jual" name="harga_jual" min="0" step="0.01"
            class="form-control @error('harga_jual') is-invalid @enderror"
            value="{{ old('harga_jual', $obat->harga_jual ?? 0) }}">
        @error('harga_jual')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label for="kode_kfa" class="form-label">Kode KFA (opsional)</label>
    <input type="text" id="kode_kfa" name="kode_kfa" maxlength="50"
        class="form-control @error('kode_kfa') is-invalid @enderror" value="{{ old('kode_kfa', $obat->kode_kfa ?? '') }}">
    @error('kode_kfa')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="form-text">Kode obat dari KFA Kemenkes untuk SATUSEHAT.</div>
</div>

<div class="form-check mb-3">
    <input type="hidden" name="aktif" value="0">
    <input type="checkbox" id="aktif" name="aktif" value="1" class="form-check-input"
        @checked(old('aktif', $obat->aktif ?? true))>
    <label for="aktif" class="form-check-label">Aktif</label>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('obat.index') }}" class="btn btn-secondary">Batal</a>
</div>
