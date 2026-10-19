@extends('layouts.app')

@section('title', 'Ubah Dokter')
@section('page-title', 'Ubah Dokter')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('dokter.update', $dokter) }}">
                @csrf
                @method('put')
                @include('master.dokter._form')
            </form>
        </div>
    </div>
@endsection
