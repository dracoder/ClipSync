<?php

namespace App\Http\Resources\Clip\Clip\Comments;

use App\Http\Resources\Clip\Clip\Clips\ClipResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user ? $this->user->name : '',
                  'clip_id' => $this->clip_id,
      'clip_user_id' => $this->clip ? $this->clip->user_id : null,
            'message' => $this->message,
            'privacy' => $this->privacy ?? 'public',
            //'comment_clip' => new ClipResource($this->commentClip),
            'video' => $this->videoUrl,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
