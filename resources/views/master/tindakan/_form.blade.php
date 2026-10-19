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
        <input type="text" id="kode" name="kode" maxlength="20"
            class="form-control @error('kode') is-invalid @enderror" value="{{ old('kode', $tindakan->kode ?? '') }}">
        @error('kode')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-8 mb-3">
        <label for="nama" class="form-label">Nama Tindakan</label>
        <input type="text" id="nama" name="nama" maxlength="150"
            class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $tindakan->nama ?? '') }}">
        @error('nama')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="poli_id" class="form-label">Poli (opsional)</label>
        <select id="poli_id" name="poli_id" class="form-select @error('poli_id') is-invalid @enderror">
            <option value="">- Berlaku semua poli -</option>
            @foreach ($daftarPoli as $poli)
                <option value="{{ $poli->id }}" @selected((int) old('poli_id', $tindakan->poli_id ?? null) === $poli->id)>
                    {{ $poli->kode }} - {{ $poli->nama }}
                </option>
            @endforeach
        </select>
        @error('poli_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="tarif" class="form-label">Tarif (Rp)</label>
        <input type="number" id="tarif" name="tarif" min="0" step="0.01"
            class="form-control @error('tarif') is-invalid @enderror"
            value="{{ old('tarif', $tindakan->tarif ?? 0) }}">
        @error('tarif')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="form-check mb-3">
    <input type="hidden" name="aktif" value="0">
    <input type="checkbox" id="aktif" name="aktif" value="1" class="form-check-input"
        @checked(old('aktif', $tindakan->aktif ?? true))>
    <label for="aktif" class="form-check-label">Aktif</label>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('tindakan.index') }}" class="btn btn-secondary">Batal</a>
</div>
