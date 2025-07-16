@extends('layouts.app')

@section('content')
<div class="container mx-auto mt-10">
    <room name="{{ $room->name }}" :room="{{ json_encode($room) }}" :user="{{ json_encode(Auth::user()) }}"></room>
</div>

@endsection
