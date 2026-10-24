@extends('layouts.app')

@section('title', 'Ubah Pasien')
@section('page-title', 'Ubah Pasien')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('pasien.update', $pasien) }}">
                @csrf
                @method('put')
                @include('master.pasien._form', ['pasien' => $pasien])
            </form>
        </div>
    </div>
@endsection