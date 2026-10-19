@extends('layouts.app')

@section('title', 'Ubah Tindakan')
@section('page-title', 'Ubah Tindakan')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('tindakan.update', $tindakan) }}">
                @csrf
                @method('put')
                @include('master.tindakan._form')
            </form>
        </div>
    </div>
@endsection
