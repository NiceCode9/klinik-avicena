<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Nomor Antrean</title>
    <style>
        @page { size: 80mm auto; margin: 4mm; }
        body { font-family: 'Courier New', monospace; text-align: center; margin: 0; }
        .klinik { font-size: 12px; font-weight: bold; }
        .nomor { font-size: 48px; font-weight: bold; margin: 8px 0; }
        .poli { font-size: 14px; margin: 4px 0; }
        .waktu { font-size: 11px; margin-top: 6px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    @php $antrean = $kunjungan->antrean->last(); @endphp
    <div class="klinik">KLINIK AVICENA</div>
    <div class="nomor">{{ $antrean?->nomor_antrean ?? '-' }}</div>
    <div class="poli">{{ $antrean?->poli?->nama ?? '-' }}</div>
    <div class="waktu">{{ now()->format('d-m-Y H:i') }}</div>
    <div class="waktu">No. Kunjungan: {{ $kunjungan->no_kunjungan }}</div>
    <div class="waktu">{{ $kunjungan->pasien?->nama_lengkap ?? '-' }}</div>

    <div class="no-print" style="margin-top:12px;">
        <button onclick="window.print()">Cetak</button>
        <a href="{{ route('kunjungan.index') }}">Kembali</a>
    </div>
</body>
</html>