@extends('layouts.app')

@section('title', 'Tambah Kategori Obat')
@section('page-title', 'Tambah Kategori Obat')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('kategori-obat.store') }}">
                @csrf
                @include('master.kategori_obat._form')
            </form>
        </div>
    </div>
@endsection
