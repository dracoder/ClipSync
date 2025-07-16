<?php

namespace App\Models\Studio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class RoomLog extends Model
{
    use HasFactory;

    protected $table = 'room_logs';

    protected $fillable = [
        'timestamp', 'room_id', 'user_id', 'room_role_id', 'description'
    ];

    protected $casts = [
        'timestamp' => 'datetime'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function room_role(){
        return $this->belongsTo(RoomRole::class,'room_role_id');
    }
}
