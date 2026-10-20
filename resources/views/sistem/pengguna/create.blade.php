@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('page-title', 'Tambah Pengguna')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('pengguna.store') }}">
                @csrf
                @include('sistem.pengguna._form')
            </form>
        </div>
    </div>
@endsection
