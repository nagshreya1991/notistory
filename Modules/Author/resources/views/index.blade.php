@extends('author::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('author.name') !!}</p>
@endsection
