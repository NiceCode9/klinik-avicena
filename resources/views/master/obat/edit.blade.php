@extends('layouts.app')

@section('title', 'Ubah Obat')
@section('page-title', 'Ubah Obat')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('obat.update', $obat) }}">
                @csrf
                @method('put')
                @include('master.obat._form')
            </form>
        </div>
    </div>
@endsection
