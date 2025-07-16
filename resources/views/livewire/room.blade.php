<div class="container mt-4">
    <room name="{{ $room->name }}" :room="{{ json_encode($room) }}" :user="{{ json_encode(Auth::user()) }}"></room>
</div>
