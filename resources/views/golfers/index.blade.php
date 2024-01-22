@extends('layouts.app')

@section('pagespecific-header-items')
    
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/datatables-styles.css') }}">

    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script type="text/javascript" charset="utf8" src="{{ asset('assets/js/datatables-min.js') }}"></script>

    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
@stop

@section('content')

    <div class="w-full">
        
        <golfers-list></golfers-list>

    </div>

@endsection