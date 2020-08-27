@extends('layouts.portal')

@section('css')

@endsection

@section('content')
    @isset($yaml)
        <pre>{!! $yaml !!}</pre>
        <br><br>
    @endisset
@endsection

@section('script')

@endsection
