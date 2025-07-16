<?php

namespace App\Models\Clip;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClipView extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    public function clip()
    {
        return $this->belongsTo(Clip::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 