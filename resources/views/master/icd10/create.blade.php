@extends('layouts.app')

@section('title', 'Tambah ICD-10')
@section('page-title', 'Tambah ICD-10')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('icd10.store') }}">
                @csrf
                @include('master.icd10._form')
            </form>
        </div>
    </div>
@endsection
