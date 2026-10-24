@extends('layouts.app')

@section('title', 'Antrean per Poli')
@section('page-title', 'Antrean per Poli')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Antrean Hari Ini</span>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge text-bg-warning" id="badge-menunggu">{{ $data->where('status', 'menunggu')->count() }} menunggu</span>
                <form method="get" action="{{ route('antrean.index') }}" class="d-flex gap-2">
                    <select name="poli_id" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
                        <option value="">Semua poli</option>
                        @foreach ($poliList as $p)
                            <option value="{{ $p->id }}" @selected((string) $poliId === (string) $p->id)>{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Poli</th>
                        <th>Status</th>
                        <th>Inisial</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody id="antrean-tbody">
                    @forelse ($data as $antrean)
                        <tr data-id="{{ $antrean->id }}">
                            <td>{{ $antrean->nomor_antrean }}</td>
                            <td>{{ $antrean->poli?->nama ?? '-' }}</td>
                            <td><span class="badge text-bg-secondary">{{ $antrean->status }}</span></td>
                            <td>{{ mb_strtoupper(mb_substr((string) ($antrean->kunjungan?->pasien?->nama_lengkap ?? ''), 0, 1)) ?: '-' }}</td>
                            <td class="text-end pe-3">
                                @izin('pendaftaran.antrean', 'ubah')
                                    @if ($antrean->status === 'menunggu')
                                        <form method="post" action="{{ route('antrean.panggil', $antrean) }}" class="d-inline">
                                            @csrf <button class="btn btn-sm btn-primary">Panggil</button>
                                        </form>
                                    @elseif ($antrean->status === 'dipanggil')
                                        <form method="post" action="{{ route('antrean.layani', $antrean) }}" class="d-inline">
                                            @csrf <button class="btn btn-sm btn-warning">Layani</button>
                                        </form>
                                    @elseif ($antrean->status === 'dilayani')
                                        <form method="post" action="{{ route('antrean.selesai', $antrean) }}" class="d-inline">
                                            @csrf <button class="btn btn-sm btn-success">Selesai</button>
                                        </form>
                                    @endif
                                    @if (in_array($antrean->status, ['menunggu', 'dipanggil', 'dilayani'], true))
                                        <form method="post" action="{{ route('antrean.batal', $antrean) }}" class="d-inline" onsubmit="return confirm('Batalkan antrean ini?')">
                                            @csrf <button class="btn btn-sm btn-danger">Batal</button>
                                        </form>
                                    @endif
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr id="antrean-kosong">
                            <td colspan="5" class="text-center text-muted py-3">Belum ada antrean hari ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        (function () {
            const poliId = @json($poliId);
            const url = @json(route('antrean.json')) + (poliId ? '?poli_id=' + encodeURIComponent(poliId) : '');
            const tbody = document.getElementById('antrean-tbody');
            const badge = document.getElementById('badge-menunggu');

            async function refresh() {
                try {
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    badge.textContent = (data.menunggu || 0) + ' menunggu';
                    if (!tbody) return;
                    tbody.innerHTML = '';
                    if (!data.items || data.items.length === 0) {
                        const tr = document.createElement('tr');
                        tr.innerHTML = '<td colspan="5" class="text-center text-muted py-3">Belum ada antrean hari ini.</td>';
                        tbody.appendChild(tr);
                        return;
                    }
                    data.items.forEach(function (item) {
                        const tr = document.createElement('tr');
                        tr.setAttribute('data-id', item.id);
                        tr.innerHTML =
                            '<td>' + item.nomor + '</td>' +
                            '<td>-</td>' +
                            '<td><span class="badge text-bg-secondary">' + item.status + '</span></td>' +
                            '<td>' + (item.inisial || '-') + '</td>' +
                            '<td class="text-end pe-3">-</td>';
                        tbody.appendChild(tr);
                    });
                } catch (e) { /* ignore */ }
            }

            setInterval(refresh, 8000);
        })();
    </script>
@endsection