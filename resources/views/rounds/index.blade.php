@extends('layouts.app')

@section('content')

    <div class="w-full">
        <manage-rounds
            role={{ Auth::user()->role }}
        ></manage-rounds>
    </div>

@endsection