@extends('layouts.app')

@section('title', 'Pemeriksaan SOAP')
@section('page-title', 'Pemeriksaan SOAP')

@section('content')
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
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <p class="mb-2">
                        Pasien: <strong>{{ $pasien?->nama_lengkap ?? '-' }}</strong>
                        ({{ $pasien?->no_rekam_medis ?? '-' }})
                    </p>
                    @if ($kunjungan?->ttv)
                        <div class="alert alert-info py-2">
                            TTV: TD {{ $kunjungan->ttv->tekanan_darah_sistolik }}/{{ $kunjungan->ttv->tekanan_darah_diastolik }},
                            Nadi {{ $kunjungan->ttv->nadi }}, Suhu {{ $kunjungan->ttv->suhu }},
                            BB {{ $kunjungan->ttv->berat_badan }} kg
                        </div>
                    @endif

                    <form method="post" action="{{ route('pemeriksaan.update', $pemeriksaan) }}">
                        @csrf
                        @method('put')
                        <input type="hidden" name="aksi" id="aksi" value="draf">

                        <div class="mb-3">
                            <label class="form-label">Subjektif (Keluhan)</label>
                            <textarea name="subjektif" class="form-control" rows="3" {{ $bolehUbah ? '' : 'readonly' }}>{{ old('subjektif', $pemeriksaan->subjektif) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Objektif (Pemeriksaan Fisik)</label>
                            <textarea name="objektif" class="form-control" rows="3" {{ $bolehUbah ? '' : 'readonly' }}>{{ old('objektif', $pemeriksaan->objektif) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Asesmen</label>
                            <textarea name="asesmen" class="form-control" rows="2" {{ $bolehUbah ? '' : 'readonly' }}>{{ old('asesmen', $pemeriksaan->asesmen) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rencana</label>
                            <textarea name="rencana" class="form-control" rows="2" {{ $bolehUbah ? '' : 'readonly' }}>{{ old('rencana', $pemeriksaan->rencana) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Diagnosis ICD-10</label>
                            <input type="text" id="icd10-search" class="form-control" placeholder="Cari kode/nama ICD-10" {{ $bolehUbah ? '' : 'readonly' }}>
                            <div id="icd10-hasil" class="list-group mt-1"></div>
                            <textarea name="diagnosis" id="diagnosis-json" class="form-control mt-2" rows="3" readonly>{{ json_encode($pemeriksaan->diagnosis->map(fn ($d) => ['icd10_id' => $d->icd10_id, 'primer' => $d->jenis === 'primer'])->values(), JSON_UNESCAPED_UNICODE) }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kode Cara Keluar</label>
                                <input type="text" name="kode_cara_keluar" class="form-control" value="{{ old('kode_cara_keluar', $kunjungan?->kode_cara_keluar) }}" {{ $bolehUbah ? '' : 'readonly' }}>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Cara Keluar</label>
                                <input type="text" name="nama_cara_keluar" class="form-control" value="{{ old('nama_cara_keluar', $kunjungan?->nama_cara_keluar) }}" {{ $bolehUbah ? '' : 'readonly' }}>
                            </div>
                        </div>

                        @if ($bolehUbah)
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" onclick="document.getElementById('aksi').value='draf'">Simpan Draf</button>
                                <button type="submit" class="btn btn-success" onclick="document.getElementById('aksi').value='final'">Simpan Final</button>
                            </div>
                        @else
                            <div class="alert alert-warning">Pemeriksaan ini tidak dapat diubah (terkunci atau bukan pembuat).</div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Riwayat Kunjungan Pasien</div>
                <div class="card-body">
                    @forelse ($riwayat as $k)
                        <div class="mb-2">
                            <strong>{{ $k->tanggal_kunjungan?->format('d-m-Y') }}</strong>
                            ({{ $k->status }})
                            @foreach ($k->pemeriksaan as $pm)
                                @foreach ($pm->diagnosis as $dg)
                                    <span class="badge text-bg-light">{{ $dg->icd10?->kode }}</span>
                                @endforeach
                            @endforeach
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada riwayat.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const input = document.getElementById('icd10-search');
            const hasil = document.getElementById('icd10-hasil');
            const diagnosisField = document.getElementById('diagnosis-json');
            if (!input) return;

            let timer = null;
            input.addEventListener('input', function () {
                clearTimeout(timer);
                const q = input.value.trim();
                if (q.length < 2) { hasil.innerHTML = ''; return; }
                timer = setTimeout(async function () {
                    const res = await fetch(@json(route('pemeriksaan.icd10')) + '?q=' + encodeURIComponent(q));
                    if (!res.ok) return;
                    const data = await res.json();
                    hasil.innerHTML = '';
                    (data || []).slice(0, 20).forEach(function (item) {
                        const a = document.createElement('a');
                        a.className = 'list-group-item list-group-item-action';
                        a.textContent = item.kode + ' - ' + item.nama_diagnosis;
                        a.href = '#';
                        a.addEventListener('click', function (e) {
                            e.preventDefault();
                            let arr = [];
                            try { arr = JSON.parse(diagnosisField.value || '[]'); } catch (e) { arr = []; }
                            arr.push({ icd10_id: item.id, primer: arr.length === 0 });
                            diagnosisField.value = JSON.stringify(arr);
                            hasil.innerHTML = '';
                            input.value = '';
                        });
                        hasil.appendChild(a);
                    });
                }, 300);
            });
        })();
    </script>
@endsection