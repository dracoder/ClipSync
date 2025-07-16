<?php

namespace App\Livewire;

use App\Models\RoomUser;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Room extends Component
{
    public $roomId, $room;
    public $is_owner = false, $user;

    public function mount($id)
    {

    }

    public function render()
    {
        return view('livewire.room');
    }
}
