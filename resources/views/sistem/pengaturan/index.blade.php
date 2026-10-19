@extends('layouts.app')

@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan')

@section('content')
    @if (session('sukses'))
        <div class="alert alert-success">{{ session('sukses') }}</div>
    @endif

    <div class="card">
        <div class="card-header">Pengaturan Sistem</div>
        <div class="card-body">
            <form method="post" action="{{ route('pengaturan.update') }}">
                @csrf
                @method('put')

                @foreach ($pengaturan as $item)
                    @php
                        $kunci = $item->kunci;
                        $tipe = \App\Models\Sys\Pengaturan::TIPE[$kunci] ?? 'teks';
                    @endphp
                    <div class="row mb-3">
                        <label class="col-md-4 col-form-label" for="nilai-{{ $kunci }}">
                            {{ $item->keterangan ?: $kunci }}
                            <div class="text-muted small"><code>{{ $kunci }}</code></div>
                        </label>
                        <div class="col-md-4">
                            @if ($tipe === 'boolean')
                                <div class="form-check mt-2">
                                    <input type="hidden" name="nilai[{{ $kunci }}]" value="0">
                                    <input type="checkbox" id="nilai-{{ $kunci }}" name="nilai[{{ $kunci }}]"
                                        value="1" class="form-check-input"
                                        @checked(old("nilai.{$kunci}", $item->nilai) == '1')>
                                    <label class="form-check-label" for="nilai-{{ $kunci }}">Ya</label>
                                </div>
                            @elseif ($tipe === 'uang')
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" min="0" step="0.01" id="nilai-{{ $kunci }}"
                                        name="nilai[{{ $kunci }}]"
                                        class="form-control @error("nilai.{$kunci}") is-invalid @enderror"
                                        value="{{ old("nilai.{$kunci}", $item->nilai) }}">
                                </div>
                            @elseif ($tipe === 'angka')
                                <input type="number" min="0" step="1" id="nilai-{{ $kunci }}"
                                    name="nilai[{{ $kunci }}]"
                                    class="form-control @error("nilai.{$kunci}") is-invalid @enderror"
                                    value="{{ old("nilai.{$kunci}", $item->nilai) }}">
                            @else
                                <input type="text" id="nilai-{{ $kunci }}" name="nilai[{{ $kunci }}]"
                                    class="form-control @error("nilai.{$kunci}") is-invalid @enderror"
                                    value="{{ old("nilai.{$kunci}", $item->nilai) }}">
                            @endif
                            @error("nilai.{$kunci}")
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                @endforeach

                @izin('sistem.pengaturan', 'ubah')
                    <button type="submit" class="btn btn-primary">Simpan</button>
                @endizin
            </form>
        </div>
    </div>
@endsection
