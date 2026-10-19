@extends('layouts.app')

@section('title', 'Tambah Obat')
@section('page-title', 'Tambah Obat')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('obat.store') }}">
                @csrf
                @include('master.obat._form')
            </form>
        </div>
    </div>
@endsection
