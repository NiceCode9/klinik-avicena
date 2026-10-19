@extends('layouts.app')

@section('title', 'Ubah Satuan')
@section('page-title', 'Ubah Satuan')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('satuan.update', $satuan) }}">
                @csrf
                @method('put')
                @include('master.satuan._form')
            </form>
        </div>
    </div>
@endsection
