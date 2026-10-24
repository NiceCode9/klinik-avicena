@extends('layouts.app')

@section('title', 'Data Pasien')
@section('page-title', 'Data Pasien')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Pasien</span>
            <div class="d-flex gap-2 ms-auto">
                <form method="get" action="{{ route('pasien.index') }}" class="d-flex gap-2">
                    <input type="text" name="cari" value="{{ $cari }}" class="form-control form-control-sm"
                        placeholder="Cari nama, no. RM, atau NIK" style="width:240px">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Cari</button>
                </form>
                @izin('pendaftaran.pasien', 'tambah')
                    <a href="{{ route('pasien.create') }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg"></i> Tambah Pasien
                    </a>
                @endizin
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:60px">No</th>
                        <th>No. RM</th>
                        <th>Nama Lengkap</th>
                        <th>Jenis Kelamin</th>
                        <th>Tanggal Lahir</th>
                        <th>No. Telepon</th>
                        <th class="text-end pe-3" style="width:160px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $pasien)
                        <tr>
                            <td class="text-center">{{ $data->firstItem() + $loop->index }}</td>
                            <td>{{ $pasien->no_rekam_medis }}</td>
                            <td>{{ $pasien->nama_lengkap }}</td>
                            <td>{{ $pasien->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                            <td>{{ $pasien->tanggal_lahir?->format('d-m-Y') ?? '-' }}</td>
                            <td>{{ $pasien->no_telepon ?? '-' }}</td>
                            <td class="text-end pe-3">
                                <a href="{{ route('pasien.show', $pasien) }}" class="btn btn-sm btn-info">Lihat</a>
                                @izin('pendaftaran.pasien', 'ubah')
                                    <a href="{{ route('pasien.edit', $pasien) }}" class="btn btn-sm btn-warning">Ubah</a>
                                @endizin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">Belum ada data pasien.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $data->links() }}
    </div>
@endsection