<?php

namespace App\Models\Clip;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function videoable()
    {
        return $this->morphTo();
    }
} 