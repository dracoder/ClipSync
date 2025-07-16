<div class="container">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="col-md-6 mt-5">
            @if (empty($createModal))
                <div class="card bg-white rounded border room-option cursor-pointer" style="height: 250px"
                    wire:click="$set('createModal', true)">
                    <div class="card-body flex justify-center h-full items-center">
                        <h4 class="font-bold text-3xl">Create Room</h4>
                    </div>
                </div>
            @else
                <div class="card room-option" style="height: 250px">
                    <div class="card-body flex justify-center h-full items-center">
                        <div class="grid grid-cols-2">
                            <div class="col-md-6">
                                <input type="text" class="input input-bordered" placeholder="Enter Room Name"
                                    wire:model="create_room">
                                @error('create_room')
                                    <span class="invalid-feedback" style="display: block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <button class="btn btn-primary ml-2" wire:click="createRoom">Create Room</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <div class="col-md-6 mt-5">
            @if (empty($joinModal))
                <div class="card bg-white rounded border room-option cursor-pointer" style="height: 250px"
                    wire:click="$set('joinModal', true)">
                    <div class="card-body flex justify-center h-full items-center">
                        <h4 class="font-bold text-3xl">Join Room</h4>
                    </div>
                </div>
            @else
                <div class="card room-option" style="height: 250px">
                    <div class="card-body flex justify-center h-full items-center">
                        <div class="grid grid-cols-2">
                            <div class="col-md-6">
                                <input type="text" class="input input-bordered" placeholder="Enter Room Id"
                                    wire:model="join_room">
                                @error('join_room')
                                    <span class="invalid-feedback" style="display: block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <button class="btn btn-primary ml-2" wire:click="joinRoom">Join Room</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @if (!empty($rooms) && count($rooms))
        <div class="flex justify-center mt-5">
            <h4 class="text-2xl">My Rooms</h4>
        </div>
        <div class="grid grid-cols-1 gap-5">
            @foreach ($rooms as $room)
                <div class="card room-option bg-white shadow p-0">
                    <div class="card-body h-100">
                        <div class="flex justify-between items-center">
                            <div class="col-9">
                                <h4 class="text-lg">{{ $room->name }}</h4>
                            </div>
                            <div class="col-3 text-right">
                                <a class="btn btn-primary btn-sm"
                                    href="{{ route('room', ['id' => $room->name]) }}">Join</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
