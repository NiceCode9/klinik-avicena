@extends('layouts.app')

@section('title', 'Tambah Supplier')
@section('page-title', 'Tambah Supplier')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('supplier.store') }}">
                @csrf
                @include('master.supplier._form')
            </form>
        </div>
    </div>
@endsection
