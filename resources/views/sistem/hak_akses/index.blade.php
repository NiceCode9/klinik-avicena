{{-- Asumsi: layout utama Anda bernama layouts.app dengan @yield('konten'). Sesuaikan jika berbeda. --}}
@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Pengaturan Hak Akses</h4>

        @if (session('sukses'))
            <div class="alert alert-success">{{ session('sukses') }}</div>
        @endif

        <form method="get" class="row g-2 mb-3">
            <div class="col-auto">
                <select name="peran" class="form-select" onchange="this.form.submit()">
                    @foreach ($daftarPeran as $p)
                        <option value="{{ $p->id }}" @selected($peran && $p->id === $peran->id)>{{ $p->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto text-muted align-self-center small">Peran <strong>admin</strong> selalu memiliki akses
                penuh dan tidak dapat diubah.</div>
        </form>

        @if ($peran)
            <form method="post" action="{{ route('hak-akses.simpan', $peran) }}">
                @csrf
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Menu</th>
                            @foreach (['lihat', 'tambah', 'ubah', 'hapus'] as $aksi)
                                <th class="text-center" style="width:90px">{{ ucfirst($aksi) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($grup as $g)
                            @php $baris = $g->rute ? collect([$g]) : $g->anak; @endphp
                            @unless ($g->rute)
                                <tr class="table-secondary">
                                    <td colspan="5"><strong>{{ $g->nama }}</strong></td>
                                </tr>
                            @endunless
                            @foreach ($baris as $m)
                                @php $h = $hak->get($m->id); @endphp
                                <tr>
                                    <td class="{{ $g->rute ? '' : 'ps-4' }}">{{ $m->nama }} <code
                                            class="small text-muted">{{ $m->kode }}</code></td>
                                    @foreach (['lihat', 'tambah', 'ubah', 'hapus'] as $aksi)
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input"
                                                name="izin[{{ $m->id }}][{{ $aksi }}]" value="1"
                                                @checked($h && $h->{'bisa_' . $aksi})>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
                <button class="btn btn-primary">Simpan Hak Akses</button>
            </form>
        @endif
    </div>
@endsection
