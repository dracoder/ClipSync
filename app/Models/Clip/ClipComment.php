<?php

namespace App\Models\Clip;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClipComment extends Model
{
    use HasFactory;

    protected $guarded = [];
    const VIDEO_PATH = 'clip-comment-videos/';
    protected $appends = ['user_name', 'videoUrl'];

    public function clip()
    {
        return $this->belongsTo(Clip::class, 'clip_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getUserNameAttribute(){
        return $this->user->name;
    }

    public function video()
    {
        return $this->hasOne(Video::class, 'videoable_id')
                    ->where('videoable_type', 'clip_comment');
    }

    public function getVideoUrlAttribute(){
        if(!empty($this->video->file)){
            return route('clip.video', ['id' => $this->id, 'type' => 'comment']);
        }
        return null;
    }
} 