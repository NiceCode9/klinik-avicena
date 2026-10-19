@extends('layouts.app')

@section('title', 'Ubah Supplier')
@section('page-title', 'Ubah Supplier')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('supplier.update', $supplier) }}">
                @csrf
                @method('put')
                @include('master.supplier._form')
            </form>
        </div>
    </div>
@endsection
