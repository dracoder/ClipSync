<?php

namespace App\Models\Studio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class RoomUser extends Model
{
    use HasFactory;

    protected $table = 'room_users';

    protected $fillable = [
        'room_id', 'user_id', 'room_role_id'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role(){
        return $this->belongsTo(RoomRole::class,'room_role_id');
    }

}
