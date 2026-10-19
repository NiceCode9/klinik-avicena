@extends('layouts.app')

@section('title', 'Ubah Kategori Obat')
@section('page-title', 'Ubah Kategori Obat')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('kategori-obat.update', $kategoriObat) }}">
                @csrf
                @method('put')
                @include('master.kategori_obat._form')
            </form>
        </div>
    </div>
@endsection
