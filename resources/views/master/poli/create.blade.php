@extends('layouts.app')

@section('title', 'Tambah Poli')
@section('page-title', 'Tambah Poli')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('poli.store') }}">
                @csrf
                @include('master.poli._form')
            </form>
        </div>
    </div>
@endsection
