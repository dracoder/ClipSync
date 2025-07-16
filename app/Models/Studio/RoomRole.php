<?php

namespace App\Models\Studio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomRole extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'room_roles';

    protected $fillable = [
        'name'
    ];
}
