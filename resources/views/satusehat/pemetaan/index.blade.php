@extends('layouts.app')

@section('title', 'Pemetaan Terminologi')
@section('page-title', 'Pemetaan Terminologi')

@section('content')
    <p class="text-muted">
        Master yang belum lengkap untuk SATUSEHAT. Pilih kategori untuk melihat daftar, lalu lengkapi lewat form edit.
    </p>

    <div class="row g-3">
        @foreach ($kategori as $kunci => $item)
            <div class="col-6 col-md-4 col-xl-3">
                <a href="{{ route('satusehat-pemetaan.index', ['kategori' => $kunci]) }}"
                    class="card h-100 text-decoration-none {{ $terpilih === $kunci ? 'border-primary' : '' }}">
                    <div class="card-body">
                        <div class="small text-muted">{{ $item['label'] }}</div>
                        <div class="fs-3 fw-semibold {{ $item['jumlah'] > 0 ? 'text-warning' : 'text-success' }}">
                            {{ $item['jumlah'] }}
                        </div>
                        <div class="small text-muted">{{ $item['keterangan'] }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    @php($aktif = $kategori[$terpilih])

    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ $aktif['label'] }} belum lengkap</span>
            <span class="badge text-bg-secondary">{{ $aktif['jumlah'] }}</span>
        </div>

        @if ($aktif['daftar']->isEmpty())
            <div class="card-body text-center text-muted py-4">
                Semua {{ strtolower($aktif['label']) }} sudah lengkap.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th>
                            <th style="width:160px">Kode</th>
                            <th>Yang belum lengkap</th>
                            <th class="text-end pe-3" style="width:120px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($aktif['daftar'] as $baris)
                            <tr>
                                <td>
                                    {{ $baris['judul'] }}
                                    @if ($baris['catatan'])
                                        <div class="small text-muted">{{ $baris['catatan'] }}</div>
                                    @endif
                                </td>
                                <td>{{ $baris['kode'] ?? '-' }}</td>
                                <td>
                                    @foreach ($baris['kurang'] as $kurang)
                                        <span class="badge text-bg-warning">{{ $kurang }}</span>
                                    @endforeach
                                </td>
                                <td class="text-end pe-3">
                                    <a href="{{ $baris['rute_edit'] }}" class="btn btn-sm btn-warning">Lengkapi</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($aktif['daftar']->hasPages())
                <div class="card-footer">
                    {{ $aktif['daftar']->appends(['kategori' => $terpilih])->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
