@extends('layouts.app')

@section('title', 'Ubah Lokasi')
@section('page-title', 'Ubah Lokasi')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('lokasi.update', $lokasi) }}">
                @csrf
                @method('put')
                @include('master.lokasi._form')
            </form>
        </div>
    </div>
@endsection
