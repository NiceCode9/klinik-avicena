@extends('layouts.app')

@section('title', 'Ubah Pengguna')
@section('page-title', 'Ubah Pengguna')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('pengguna.update', $pengguna) }}">
                @csrf
                @method('put')
                @include('sistem.pengguna._form')
            </form>
        </div>
    </div>
@endsection
