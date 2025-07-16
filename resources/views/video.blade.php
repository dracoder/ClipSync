@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4" id="app">
    <video-call :user="{{ json_encode(Auth::user()) }}"></video-call>
</div>
@endsection
