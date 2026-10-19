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
    <label for="nama" class="form-label">Nama Kategori</label>
    <input type="text" id="nama" name="nama" maxlength="100"
        class="form-control @error('nama') is-invalid @enderror"
        value="{{ old('nama', $kategoriObat->nama ?? '') }}">
    @error('nama')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('kategori-obat.index') }}" class="btn btn-secondary">Batal</a>
</div>
