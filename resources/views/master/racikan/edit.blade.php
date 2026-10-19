@extends('layouts.app')

@section('title', 'Ubah Menu Racikan')
@section('page-title', 'Ubah Menu Racikan')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('racikan.update', $racikan) }}">
                @csrf
                @method('put')
                @include('master.racikan._form')
            </form>
        </div>
    </div>
@endsection
