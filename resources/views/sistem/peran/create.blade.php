@extends('layouts.app')

@section('title', 'Tambah Peran')
@section('page-title', 'Tambah Peran')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('peran.store') }}">
                @csrf
                @include('sistem.peran._form')
            </form>
        </div>
    </div>
@endsection
