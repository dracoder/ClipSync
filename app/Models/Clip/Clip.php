<?php

namespace App\Models\Clip;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clip extends Model
{
    use HasFactory;

    protected $guarded = [];
    const VIDEO_PATH = 'clip-videos/';
    protected $appends = ['videoUrl'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(ClipComment::class, 'clip_id');
    }

    public function video()
    {
        return $this->hasOne(Video::class, 'videoable_id')
                    ->where('videoable_type', 'clip');
    }

    public function views()
    {
        return $this->hasMany(ClipView::class);
    }

    public function getVideoUrlAttribute(){
        if(!empty($this->video->file)){
            return route('clip.video', ['id' => $this->id, 'type' => 'clip']);
        }
        return null;
    }
} 