@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    {{-- Statistic boxes: replace the placeholder values with real data. --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3>0</h3>
                    <p>Pasien Terdaftar</p>
                </div>
                <i class="small-box-icon bi bi-people"></i>
                <a href="#" class="small-box-footer">Selengkapnya <i class="bi bi-arrow-right-circle"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>0</h3>
                    <p>Dokter</p>
                </div>
                <i class="small-box-icon bi bi-person-badge"></i>
                <a href="#" class="small-box-footer">Selengkapnya <i class="bi bi-arrow-right-circle"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3>0</h3>
                    <p>Jadwal Hari Ini</p>
                </div>
                <i class="small-box-icon bi bi-calendar-check"></i>
                <a href="#" class="small-box-footer">Selengkapnya <i class="bi bi-arrow-right-circle"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-danger">
                <div class="inner">
                    <h3>0</h3>
                    <p>Kunjungan Bulan Ini</p>
                </div>
                <i class="small-box-icon bi bi-clipboard2-pulse"></i>
                <a href="#" class="small-box-footer">Selengkapnya <i class="bi bi-arrow-right-circle"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Selamat Datang</h3>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        Ini adalah halaman dashboard AdminLTE 4. Silakan ubah isi
                        <code>resources/views/dashboard.blade.php</code> untuk mulai membangun aplikasi.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Kartu Contoh</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-tool" data-lte-toggle="card-collapse" aria-label="Lipat kartu">
                            <i data-lte-icon="expand" class="bi bi-dash-lg"></i>
                            <i data-lte-icon="collapse" class="bi bi-plus-lg"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-tool" data-lte-toggle="card-remove" aria-label="Hapus kartu">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    Klik ikon minus untuk melipat kartu, atau ikon silang untuk menghapusnya.
                </div>
            </div>
        </div>
    </div>
@endsection
