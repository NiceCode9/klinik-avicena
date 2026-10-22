@extends('layouts.app')

@section('title', 'Ubah Tenaga Kesehatan')
@section('page-title', 'Ubah Tenaga Kesehatan')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('nakes.update', $nakes) }}">
                @csrf
                @method('put')
                @include('master.nakes._form')
            </form>
        </div>
    </div>
@endsection
