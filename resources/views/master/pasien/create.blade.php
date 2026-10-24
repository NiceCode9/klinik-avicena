@extends('layouts.app')

@section('title', 'Tambah Pasien')
@section('page-title', 'Tambah Pasien')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('pasien.store') }}">
                @csrf
                @include('master.pasien._form')
            </form>
        </div>
    </div>
@endsection