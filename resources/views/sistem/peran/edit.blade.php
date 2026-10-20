@extends('layouts.app')

@section('title', 'Ubah Peran')
@section('page-title', 'Ubah Peran')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('peran.update', $peran) }}">
                @csrf
                @method('put')
                @include('sistem.peran._form')
            </form>
        </div>
    </div>
@endsection
