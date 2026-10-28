@extends('layouts.app')

@section('title', 'Input Hasil Lab')
@section('page-title', 'Input Hasil Lab')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif
    @if (session('galat'))
        <div class="alert alert-danger">{{ session('galat') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="post" action="{{ route('lab-hasil.update', $permintaan) }}">
        @csrf
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Pemeriksaan</th>
                        <th>Parameter</th>
                        <th>Hasil</th>
                        <th>Satuan</th>
                        <th>Nilai Rujukan</th>
                        <th>Penanda</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($permintaan->permintaanDetail as $detail)
                        @php
                            $spesimenStatus = $detail->spesimen?->status;
                            $bolehInput = ! $permintaan->dikunci && $spesimenStatus === 'diterima';
                        @endphp
                        @foreach ($detail->pemeriksaanLab->parameterLab as $param)
                            @php
                                $hasil = $detail->hasil->firstWhere('parameter_lab_id', $param->id);
                            @endphp
                            <tr>
                                <td>{{ $detail->pemeriksaanLab->nama }}</td>
                                <td>{{ $param->nama_parameter }}</td>
                                <td>
                                    <input type="text"
                                           name="hasil[{{ $detail->id }}][{{ $param->id }}]"
                                           value="{{ old('hasil.'.$detail->id.'.'.$param->id, $hasil?->nilai) }}"
                                           class="form-control form-control-sm"
                                           @disabled(! $bolehInput)>
                                </td>
                                <td>{{ $param->satuan }}</td>
                                <td>{{ $param->nilai_rujukan_min }} - {{ $param->nilai_rujukan_max }}</td>
                                <td>{{ $hasil?->penanda }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
        @izin('lab.hasil', 'tambah')
            <button type="submit" class="btn btn-primary mb-3" @disabled($permintaan->dikunci)>Simpan Hasil</button>
        @endizin
    </form>

    @if (! $permintaan->dikunci)
        <form method="post" action="{{ route('lab-hasil.kunci', $permintaan) }}" id="form-kunci">
            @csrf
            <div class="card">
                <div class="card-header">Kunci Hasil</div>
                <div class="card-body">
                    <div class="mb-3" style="max-width:360px">
                        <label class="form-label">Dokter Lab (wajib rujukan) *</label>
                        <select name="dokter_id" id="dokter_id" class="form-select" required>
                            <option value="">-- pilih dokter --</option>
                            @foreach (\App\Models\Master\Dokter::where('aktif', true)->orderBy('nama')->get() as $d)
                                <option value="{{ $d->id }}" @selected($permintaan->dokter_id === $d->id)>{{ $d->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    @izin('lab.hasil', 'ubah')
                        <button type="submit" class="btn btn-danger" id="btn-kunci" @disabled($permintaan->dokter_id === null)>Kunci Hasil</button>
                    @endizin
                </div>
            </div>
        </form>
    @else
        <div class="alert alert-secondary">Hasil sudah terkunci.</div>
    @endif
@endsection

@push('scripts')
<script>
    const sel = document.getElementById('dokter_id');
    const btn = document.getElementById('btn-kunci');
    if (sel && btn) {
        const update = () => { btn.disabled = !sel.value; };
        sel.addEventListener('change', update);
        update();
    }
</script>
@endpush