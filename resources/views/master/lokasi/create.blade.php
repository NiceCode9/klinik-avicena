@extends('layouts.app')

@section('title', 'Tambah Lokasi')
@section('page-title', 'Tambah Lokasi')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('lokasi.store') }}">
                @csrf
                @include('master.lokasi._form')
            </form>
        </div>
    </div>
@endsection
