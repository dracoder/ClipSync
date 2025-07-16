<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class Home extends Component
{
    public $create_room, $join_room;
    public $joinModal = false;
    public $createModal = false;
    public $rooms = [];

    public function mount()
    {
        $this->rooms = \App\Models\Room::where('owner_id', Auth::id())->get();
    }

    public function render()
    {
        return view('livewire.home');
    }

    public function createRoom()
    {
        $this->validate([
            'create_room' => 'required|unique:rooms,name',
        ]);
        $room = \App\Models\Room::create([
            'name' => $this->create_room,
            'owner_id' => Auth::id()
        ]);
        return redirect()->route('room', ['id' => $room->name]);
    }

    public function joinRoom()
    {
        $this->validate([
            'join_room' => 'required|exists:rooms,name',
        ]);
        return redirect()->route('room', ['id' => $this->join_room]);
    }
}
