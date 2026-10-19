@extends('layouts.app')

@section('title', 'Ubah ICD-10')
@section('page-title', 'Ubah ICD-10')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('icd10.update', $icd10) }}">
                @csrf
                @method('put')
                @include('master.icd10._form')
            </form>
        </div>
    </div>
@endsection
