<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Hasil Lab - {{ $permintaan->no_permintaan }}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        body { font-family: 'Times New Roman', serif; font-size: 11pt; color: #000; }
        .kop { text-align: center; border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 10px; }
        .kop h2 { margin: 0; font-size: 14pt; }
        .kop p { margin: 0; font-size: 10pt; }
        .meta { margin-bottom: 10px; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 4px; vertical-align: top; }
        h3 { font-size: 12pt; margin: 10px 0 4px; }
        table.hasil { width: 100%; border-collapse: collapse; }
        table.hasil th, table.hasil td { border: 1px solid #000; padding: 4px 6px; font-size: 10pt; }
        table.hasil th { background: #f2f2f2; }
        .footer { margin-top: 30px; width: 100%; }
        .footer td { width: 50%; text-align: center; vertical-align: top; }
        .ttd { height: 60px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="kop">
        <h2>KLINIK AVICENA</h2>
        <p>Jl. Contoh No. 1 - Telp. (000) 000000</p>
        <p><strong>HASIL PEMERIKSAAN LABORATORIUM</strong></p>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td style="width:20%">No. Permintaan</td>
                <td style="width:30%">: {{ $permintaan->no_permintaan }}</td>
                <td style="width:20%">Tanggal Hasil</td>
                <td style="width:30%">: {{ optional($permintaan->dikunci_pada)->format('d-m-Y H:i') ?? $permintaan->updated_at->format('d-m-Y H:i') }}</td>
            </tr>
            <tr>
                <td>Nama Pasien</td>
                <td>: {{ $permintaan->kunjungan->pasien->nama_lengkap ?? '-' }}</td>
                <td>No. RM</td>
                <td>: {{ $permintaan->kunjungan->pasien->no_rekam_medis ?? '-' }}</td>
            </tr>
            <tr>
                <td>Jenis Kelamin</td>
                <td>: {{ $permintaan->kunjungan->pasien->jenis_kelamin ?? '-' }}</td>
                <td>Tanggal Lahir</td>
                <td>: {{ optional($permintaan->kunjungan->pasien->tanggal_lahir)->format('d-m-Y') ?? '-' }}</td>
            </tr>
            <tr>
                <td>Dokter Lab</td>
                <td>: {{ $permintaan->dokter->nama ?? '-' }}</td>
                <td>Sumber</td>
                <td>: {{ ucfirst($permintaan->sumber) }}</td>
            </tr>
        </table>
    </div>

    <h3>Hasil Pemeriksaan</h3>
    <table class="hasil">
        <thead>
            <tr>
                <th>No</th>
                <th>Pemeriksaan</th>
                <th>Parameter</th>
                <th>Hasil</th>
                <th>Satuan</th>
                <th>Nilai Rujukan</th>
                <th>Penanda</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach ($permintaan->permintaanDetail as $detail)
                @foreach ($detail->pemeriksaanLab->parameterLab as $param)
                    @php
                        $hasil = $detail->hasil->firstWhere('parameter_lab_id', $param->id);
                    @endphp
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td>{{ $detail->pemeriksaanLab->nama }}</td>
                        <td>{{ $param->nama_parameter }}</td>
                        <td>{{ $hasil?->nilai ?? '-' }}</td>
                        <td>{{ $hasil?->satuan ?? $param->satuan }}</td>
                        <td>{{ $hasil?->nilai_rujukan ?? ($param->nilai_rujukan_min.' - '.$param->nilai_rujukan_max) }}</td>
                        <td>{{ $hasil?->penanda ?? '-' }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <p style="font-size:9pt; margin-top:8px;">Catatan: Penanda otomatis berdasarkan nilai rujukan (normal/rendah/tinggi). Hasil ini hanya berlaku untuk sampel yang diterima laboratorium.</p>

    <table class="footer">
        <tr>
            <td>
                <p>Petugas Laboratorium</p>
                <div class="ttd"></div>
                <p>( ____________________ )</p>
            </td>
            <td>
                <p>Dokter Penanggung Jawab</p>
                <div class="ttd"></div>
                <p>( {{ $permintaan->dokter->nama ?? '____________________' }} )</p>
            </td>
        </tr>
    </table>

    <div class="no-print" style="margin-top:16px; text-align:center;">
        <button onclick="window.print()">Cetak</button>
        <a href="{{ route('lab-hasil.index') }}">Kembali</a>
    </div>
</body>
</html>