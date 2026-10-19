@extends('layouts.app')

@section('title', 'Tambah Menu Racikan')
@section('page-title', 'Tambah Menu Racikan')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('racikan.store') }}">
                @csrf
                @include('master.racikan._form')
            </form>
        </div>
    </div>
@endsection
