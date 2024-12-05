@extends('subscriber::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('subscriber.name') !!}</p>
@endsection
