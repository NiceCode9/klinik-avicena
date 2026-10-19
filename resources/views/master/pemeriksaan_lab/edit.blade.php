@extends('layouts.app')

@section('title', 'Ubah Pemeriksaan Lab')
@section('page-title', 'Ubah Pemeriksaan Lab')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('pemeriksaan-lab.update', $pemeriksaanLab) }}">
                @csrf
                @method('put')
                @include('master.pemeriksaan_lab._form')
            </form>
        </div>
    </div>
@endsection
