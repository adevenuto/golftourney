@extends('layouts.app')

@section('pagespecific-header-items')
    
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/datatables-styles.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/datatables-responsive.css') }}">

    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script type="text/javascript" charset="utf8" src="{{ asset('assets/js/datatables-min.js') }}"></script>

    <script type="text/javascript" charset="utf8" src="{{ asset('assets/js/datatables-buttons.min.js') }}"></script>
    <script type="text/javascript" charset="utf8" src="{{ asset('assets/js/datatables-jszip.min.js') }}"></script>
    <script type="text/javascript" charset="utf8" src="{{ asset('assets/js/datatables-pdfmake.min.js') }}"></script>
    <script type="text/javascript" charset="utf8" src="{{ asset('assets/js/datatables-pdfmake-fonts.min.js') }}"></script>
    <script type="text/javascript" charset="utf8" src="{{ asset('assets/js/datatables-buttons-html5.min.js') }}"></script>


    <script type="text/javascript" charset="utf8" src="{{ asset('assets/js/datatables-responsive1.min.js') }}"></script>
    <script type="text/javascript" charset="utf8" src="{{ asset('assets/js/datatables-responsive2.min.js') }}"></script>
@stop

@section('content')
    <div class="w-full">
        <golfers-list 
            role={{ Auth::user()->role }}
        ></golfers-list>
    </div>
@endsection