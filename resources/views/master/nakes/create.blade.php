@extends('layouts.app')

@section('title', 'Tambah Tenaga Kesehatan')
@section('page-title', 'Tambah Tenaga Kesehatan')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('nakes.store') }}">
                @csrf
                @include('master.nakes._form')
            </form>
        </div>
    </div>
@endsection
