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
    <label for="nama" class="form-label">Nama Peran</label>
    <input type="text" id="nama" name="nama" maxlength="50"
        class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $peran->nama ?? '') }}"
        @readonly(($peran->nama ?? null) === 'admin')>
    @error('nama')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    @if (($peran->nama ?? null) === 'admin')
        <div class="form-text">Nama peran admin dilindungi dan tidak dapat diubah.</div>
    @endif
</div>

<div class="mb-3">
    <label for="keterangan" class="form-label">Keterangan (opsional)</label>
    <textarea id="keterangan" name="keterangan" rows="2" maxlength="255"
        class="form-control @error('keterangan') is-invalid @enderror">{{ old('keterangan', $peran->keterangan ?? '') }}</textarea>
    @error('keterangan')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('peran.index') }}" class="btn btn-secondary">Batal</a>
</div>
