@extends('layouts.app')

@section('title', 'Tambah Satuan')
@section('page-title', 'Tambah Satuan')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('satuan.store') }}">
                @csrf
                @include('master.satuan._form')
            </form>
        </div>
    </div>
@endsection
