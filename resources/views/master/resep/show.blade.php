@extends('layouts.app')

@section('title', 'Detail Resep')
@section('page-title', 'Detail Resep')

@section('content')
    <div class="card">
        <div class="card-body">
            <p>No. Resep: <strong>{{ $resep->no_resep }}</strong> ({{ $resep->status }})</p>
            <p>Pasien: {{ $resep->pemeriksaan?->pasien?->nama_lengkap ?? '-' }}</p>

            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Jenis</th>
                        <th>Obat/Racikan</th>
                        <th>Jumlah</th>
                        <th>Aturan Pakai</th>
                        <th>Harga Satuan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($resep->resepItem as $item)
                        <tr>
                            <td>{{ $item->jenis }}</td>
                            <td>
                                @if ($item->jenis === 'reguler')
                                    {{ $item->obat?->nama ?? '-' }}
                                    @if (! $item->obat?->kode_kfa)
                                        <span class="badge text-bg-warning">Belum terpetakan ke KFA</span>
                                    @endif
                                @else
                                    {{ $item->nama_racikan ?? '-' }}
                                @endif
                            </td>
                            <td>{{ $item->jumlah }}</td>
                            <td>{{ $item->aturan_pakai }}</td>
                            <td>{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <a href="{{ route('resep.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
@endsection