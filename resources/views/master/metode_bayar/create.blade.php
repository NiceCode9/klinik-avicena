@extends('layouts.app')

@section('title', 'Tambah Metode Bayar')
@section('page-title', 'Tambah Metode Bayar')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('metode-bayar.store') }}">
                @csrf
                @include('master.metode_bayar._form')
            </form>
        </div>
    </div>
@endsection
