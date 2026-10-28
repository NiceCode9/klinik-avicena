@extends('layouts.app')

@section('title', 'Antrean Lab')
@section('page-title', 'Antrean Lab')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif
    @if (session('galat'))
        <div class="alert alert-danger">{{ session('galat') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No Permintaan</th>
                        <th>Pasien</th>
                        <th>Antrean</th>
                        <th>Dokter Lab</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($permintaan as $p)
                        <tr>
                            <td>{{ $p->no_permintaan }}</td>
                            <td>{{ $p->kunjungan->pasien->nama_lengkap ?? '-' }}</td>
                            <td>{{ $p->antrean->nomor_antrean ?? '-' }}</td>
                            <td>
                                <form method="post" action="{{ route('lab-antrean.ubah-dokter', $p) }}" class="d-flex gap-2">
                                    @csrf
                                    <select name="dokter_id" class="form-select form-select-sm">
                                        <option value="">-- dokter --</option>
                                        @foreach (\App\Models\Master\Dokter::where('aktif', true)->orderBy('nama')->get() as $d)
                                            <option value="{{ $d->id }}" @selected($p->dokter_id === $d->id)>{{ $d->nama }}</option>
                                        @endforeach
                                    </select>
                                    @izin('lab.antrean', 'ubah')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Simpan</button>
                                    @endizin
                                </form>
                            </td>
                            <td>{{ $p->status }}</td>
                            <td class="d-flex gap-2">
                                @izin('lab.antrean', 'ubah')
                                    @if ($p->status === 'menunggu')
                                        <form method="post" action="{{ route('lab-antrean.mulai', $p) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary">Mulai</button>
                                        </form>
                                    @endif
                                @endizin
                                <a href="{{ route('spesimen.index') }}" class="btn btn-sm btn-outline-secondary">Spesimen</a>
                                <a href="{{ route('lab-hasil.edit', $p) }}" class="btn btn-sm btn-outline-secondary">Hasil</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $permintaan->links() }}</div>
    </div>
@endsection