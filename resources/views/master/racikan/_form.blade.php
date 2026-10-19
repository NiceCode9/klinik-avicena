@php
    $bahan = old('bahan', isset($racikan)
        ? $racikan->racikanBahan->map(fn ($b) => [
            'id' => $b->id,
            'obat_id' => $b->obat_id,
            'jumlah' => $b->jumlah,
            'satuan_id' => $b->satuan_id,
        ])->all()
        : []);
    $bahan = $bahan ?: [[]];
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
    <div class="col-md-6 mb-3">
        <label for="nama_menu" class="form-label">Nama Menu</label>
        <input type="text" id="nama_menu" name="nama_menu" maxlength="150"
            class="form-control @error('nama_menu') is-invalid @enderror"
            value="{{ old('nama_menu', $racikan->nama_menu ?? '') }}">
        @error('nama_menu')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="keterangan" class="form-label">Keterangan (opsional)</label>
        <input type="text" id="keterangan" name="keterangan" maxlength="255"
            class="form-control @error('keterangan') is-invalid @enderror"
            value="{{ old('keterangan', $racikan->keterangan ?? '') }}">
        @error('keterangan')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="form-check mb-3">
    <input type="hidden" name="aktif" value="0">
    <input type="checkbox" id="aktif" name="aktif" value="1" class="form-check-input"
        @checked(old('aktif', $racikan->aktif ?? true))>
    <label for="aktif" class="form-check-label">Aktif</label>
</div>

<h6 class="mt-3 mb-1">Bahan Racikan</h6>
<p class="text-muted small mb-2">Jumlah bahan adalah total untuk satu item racikan. Baris tanpa obat diabaikan.</p>

<div class="table-responsive">
    <table class="table table-sm align-middle" id="tabel-bahan">
        <thead class="table-light">
            <tr>
                <th>Obat</th>
                <th style="width:140px">Jumlah</th>
                <th style="width:170px">Satuan</th>
                <th style="width:70px"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bahan as $index => $item)
                @include('master.racikan._baris_bahan', ['index' => $index, 'baris' => $item])
            @endforeach
        </tbody>
    </table>
</div>

<button type="button" class="btn btn-sm btn-outline-primary" id="tambah-bahan">
    <i class="bi bi-plus-lg"></i> Tambah Baris
</button>

<hr class="my-4">

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('racikan.index') }}" class="btn btn-secondary">Batal</a>
</div>

<template id="template-bahan">
    @include('master.racikan._baris_bahan', ['index' => '__INDEX__', 'baris' => []])
</template>

@push('scripts')
    <script>
        (function () {
            var tabel = document.querySelector('#tabel-bahan tbody');
            var template = document.getElementById('template-bahan');
            var berikutnya = {{ count($bahan) }};

            document.getElementById('tambah-bahan').addEventListener('click', function () {
                var baris = template.content.cloneNode(true);
                baris.querySelectorAll('input, select').forEach(function (input) {
                    input.name = input.name.replace('__INDEX__', berikutnya);
                });
                tabel.appendChild(baris);
                berikutnya++;
            });

            tabel.addEventListener('click', function (event) {
                var tombol = event.target.closest('.hapus-bahan');
                if (tombol) {
                    tombol.closest('tr').remove();
                }
            });

            // Satuan mengikuti satuan obat, tetap bisa diganti manual.
            tabel.addEventListener('change', function (event) {
                var pilihObat = event.target.closest('.pilih-obat');
                if (!pilihObat) {
                    return;
                }

                var opsi = pilihObat.selectedOptions[0];
                var satuan = pilihObat.closest('tr').querySelector('.pilih-satuan');

                if (opsi && opsi.dataset.satuan) {
                    satuan.value = opsi.dataset.satuan;
                }
            });
        })();
    </script>
@endpush
