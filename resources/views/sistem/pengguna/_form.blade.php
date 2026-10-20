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
        <label for="nama" class="form-label">Nama</label>
        <input type="text" id="nama" name="nama" maxlength="255"
            class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $pengguna->nama ?? '') }}">
        @error('nama')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" id="username" name="username" maxlength="255"
            class="form-control @error('username') is-invalid @enderror"
            value="{{ old('username', $pengguna->username ?? '') }}">
        @error('username')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" id="email" name="email" maxlength="255"
            class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $pengguna->email ?? '') }}">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="peran_id" class="form-label">Peran</label>
        <select id="peran_id" name="peran_id" class="form-select @error('peran_id') is-invalid @enderror">
            <option value="">— Pilih peran —</option>
            @foreach ($daftarPeran as $peran)
                <option value="{{ $peran->id }}" @selected((int) old('peran_id', $pengguna->peran_id ?? 0) === $peran->id)>
                    {{ $peran->nama }}
                </option>
            @endforeach
        </select>
        @error('peran_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="dokter_id" class="form-label">Dokter (opsional)</label>
        <select id="dokter_id" name="dokter_id" class="form-select @error('dokter_id') is-invalid @enderror">
            <option value="">— Bukan dokter —</option>
            @foreach ($daftarDokter as $dokter)
                <option value="{{ $dokter->id }}" @selected((int) old('dokter_id', $pengguna->dokter_id ?? 0) === $dokter->id)>
                    {{ $dokter->nama }}
                </option>
            @endforeach
        </select>
        @error('dokter_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-check mb-3 ms-2">
        <input type="hidden" name="aktif" value="0">
        <input type="checkbox" id="aktif" name="aktif" value="1" class="form-check-input"
            @checked(old('aktif', $pengguna->aktif ?? true))>
        <label for="aktif" class="form-check-label">Aktif</label>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="password" class="form-label">
            Kata Sandi {{ isset($pengguna) ? '(kosongkan jika tidak diganti)' : '' }}
        </label>
        <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
            autocomplete="new-password">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="password_confirmation" class="form-label">Ulangi Kata Sandi</label>
        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
            autocomplete="new-password">
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('pengguna.index') }}" class="btn btn-secondary">Batal</a>
</div>
