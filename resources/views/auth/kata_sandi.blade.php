@extends('layouts.app')

@section('title', 'Ganti Kata Sandi')
@section('page-title', 'Ganti Kata Sandi')

@section('content')
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ganti Kata Sandi</h3>
                </div>
                <form method="post" action="{{ route('kata-sandi.update') }}">
                    @csrf
                    @method('put')

                    <div class="card-body">
                        @if (session('sukses'))
                            <div class="alert alert-success">{{ session('sukses') }}</div>
                        @endif

                        <div class="mb-3">
                            <label for="kata_sandi_lama" class="form-label">Kata sandi lama</label>
                            <input type="password" id="kata_sandi_lama" name="kata_sandi_lama"
                                class="form-control @error('kata_sandi_lama') is-invalid @enderror"
                                required autocomplete="current-password" autofocus>
                            @error('kata_sandi_lama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="kata_sandi" class="form-label">Kata sandi baru</label>
                            <input type="password" id="kata_sandi" name="kata_sandi"
                                class="form-control @error('kata_sandi') is-invalid @enderror"
                                required autocomplete="new-password">
                            @error('kata_sandi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Minimal 8 karakter.</div>
                        </div>

                        <div class="mb-0">
                            <label for="kata_sandi_confirmation" class="form-label">Ulangi kata sandi baru</label>
                            <input type="password" id="kata_sandi_confirmation" name="kata_sandi_confirmation"
                                class="form-control" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
