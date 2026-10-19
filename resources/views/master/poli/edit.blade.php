@extends('layouts.app')

@section('title', 'Ubah Poli')
@section('page-title', 'Ubah Poli')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('poli.update', $poli) }}">
                @csrf
                @method('put')
                @include('master.poli._form')
            </form>
        </div>
    </div>
@endsection
