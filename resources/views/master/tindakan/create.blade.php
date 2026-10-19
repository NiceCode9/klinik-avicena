@extends('layouts.app')

@section('title', 'Tambah Tindakan')
@section('page-title', 'Tambah Tindakan')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('tindakan.store') }}">
                @csrf
                @include('master.tindakan._form')
            </form>
        </div>
    </div>
@endsection
