@extends('layouts.app')

@section('title', 'Ubah Metode Bayar')
@section('page-title', 'Ubah Metode Bayar')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('metode-bayar.update', $metodeBayar) }}">
                @csrf
                @method('put')
                @include('master.metode_bayar._form')
            </form>
        </div>
    </div>
@endsection
