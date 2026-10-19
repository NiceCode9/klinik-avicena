@extends('layouts.app')

@section('title', 'Tambah Pemeriksaan Lab')
@section('page-title', 'Tambah Pemeriksaan Lab')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('pemeriksaan-lab.store') }}">
                @csrf
                @include('master.pemeriksaan_lab._form')
            </form>
        </div>
    </div>
@endsection
