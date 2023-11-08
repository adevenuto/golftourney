@extends('layouts.app')

@section('pagespecific-header-items')
    
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/datatables-styles.css') }}">
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script type="text/javascript" charset="utf8" src="{{ asset('assets/js/datatables-min.js') }}"></script>
    
@stop

@section('content')

    <div class="w-full">
        
        <golfers-list></golfers-list>

    </div>

@endsection