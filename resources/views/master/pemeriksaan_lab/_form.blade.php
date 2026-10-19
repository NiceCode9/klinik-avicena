@php
    $baris = old('parameter', isset($pemeriksaanLab)
        ? $pemeriksaanLab->parameterLab->map(fn ($p) => [
            'id' => $p->id,
            'nama_parameter' => $p->nama_parameter,
            'satuan' => $p->satuan,
            'nilai_rujukan_min' => $p->nilai_rujukan_min,
            'nilai_rujukan_max' => $p->nilai_rujukan_max,
            'keterangan_rujukan' => $p->keterangan_rujukan,
        ])->all()
        : []);
    $baris = $baris ?: [[]];
@endphp

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
            class="form-control @error('kode') is-invalid @enderror"
            value="{{ old('kode', $pemeriksaanLab->kode ?? '') }}">
        @error('kode')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-8 mb-3">
        <label for="nama" class="form-label">Nama Pemeriksaan</label>
        <input type="text" id="nama" name="nama" maxlength="150"
            class="form-control @error('nama') is-invalid @enderror"
            value="{{ old('nama', $pemeriksaanLab->nama ?? '') }}">
        @error('nama')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="tarif" class="form-label">Tarif (Rp)</label>
        <input type="number" id="tarif" name="tarif" min="0" step="0.01"
            class="form-control @error('tarif') is-invalid @enderror"
            value="{{ old('tarif', $pemeriksaanLab->tarif ?? 0) }}">
        @error('tarif')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-8 d-flex align-items-end mb-3">
        <div class="form-check">
            <input type="hidden" name="aktif" value="0">
            <input type="checkbox" id="aktif" name="aktif" value="1" class="form-check-input"
                @checked(old('aktif', $pemeriksaanLab->aktif ?? true))>
            <label for="aktif" class="form-check-label">Aktif</label>
        </div>
    </div>
</div>

<h6 class="mt-3 mb-1">Parameter Pemeriksaan</h6>
<p class="text-muted small mb-2">Urutan parameter mengikuti urutan baris. Baris dengan nama kosong diabaikan.</p>

<div class="table-responsive">
    <table class="table table-sm align-middle" id="tabel-parameter">
        <thead class="table-light">
            <tr>
                <th>Nama Parameter</th>
                <th style="width:130px">Satuan</th>
                <th style="width:120px">Rujukan Min</th>
                <th style="width:120px">Rujukan Max</th>
                <th style="width:220px">Keterangan</th>
                <th style="width:70px"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($baris as $index => $item)
                @include('master.pemeriksaan_lab._baris_parameter', ['index' => $index, 'baris' => $item])
            @endforeach
        </tbody>
    </table>
</div>

<button type="button" class="btn btn-sm btn-outline-primary" id="tambah-parameter">
    <i class="bi bi-plus-lg"></i> Tambah Baris
</button>

<hr class="my-4">

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('pemeriksaan-lab.index') }}" class="btn btn-secondary">Batal</a>
</div>

<template id="template-parameter">
    @include('master.pemeriksaan_lab._baris_parameter', ['index' => '__INDEX__', 'baris' => []])
</template>

@push('scripts')
    <script>
        (function () {
            var tabel = document.querySelector('#tabel-parameter tbody');
            var template = document.getElementById('template-parameter');
            var berikutnya = {{ count($baris) }};

            document.getElementById('tambah-parameter').addEventListener('click', function () {
                var baris = template.content.cloneNode(true);
                baris.querySelectorAll('input').forEach(function (input) {
                    input.name = input.name.replace('__INDEX__', berikutnya);
                });
                tabel.appendChild(baris);
                berikutnya++;
            });

            tabel.addEventListener('click', function (event) {
                var tombol = event.target.closest('.hapus-parameter');
                if (tombol) {
                    tombol.closest('tr').remove();
                }
            });
        })();
    </script>
@endpush
