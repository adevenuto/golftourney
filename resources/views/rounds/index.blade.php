@extends('layouts.app')

@section('content')

    <div class="w-full">
        <manage-rounds
            role={{ Auth::user()->role->value }}
        ></manage-rounds>
    </div>

@endsection