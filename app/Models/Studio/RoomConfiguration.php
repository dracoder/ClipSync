<?php

namespace App\Models\Studio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomConfiguration extends Model
{
    use HasFactory;

    protected $fillable = ['room_id', 'admin_password','guest_password','configuration','restrict_guest','restrict_admin'];

    protected $casts = [
        'configuration' => 'array',
    ];
}
