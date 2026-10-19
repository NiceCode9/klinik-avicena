@extends('layouts.app')

@section('title', 'Tambah Dokter')
@section('page-title', 'Tambah Dokter')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('dokter.store') }}">
                @csrf
                @include('master.dokter._form')
            </form>
        </div>
    </div>
@endsection
