<?php

namespace App\Models\Studio;

use App\Models\User;
use App\Models\Studio\Room;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'sender_id',
        'sender_name',
        'message',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
