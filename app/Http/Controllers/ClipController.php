<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Clip\Clip;
use App\Models\Clip\ClipComment;
use App\Models\Clip\Video;
use Illuminate\Support\Facades\Storage;

class ClipController extends Controller
{
    public function getVideoFile($id, $type){
            if($type == 'clip'){
        return $this->getClipVideoFile($id);
        }else if($type == 'comment'){
            return $this->getCommentVideoFile($id);
        }
        return response()->json(['error' => 'Invalid type'], 400);
    }

    public function getClipVideoFile($id){
        $clip = Clip::findOrFail($id);
        $video = Video::where('videoable_id', $clip->id)->where('videoable_type', 'clip')->first();
        if (!empty($video->file)) {
            $filePath = Clip::VIDEO_PATH . $clip->id . '/' . $video->file;
            if (Storage::exists('public/'. $filePath)) {
                return response()->file(storage_path('app/public/' . $filePath));
            }
        }
        return response()->json(['error' => 'File not found'], 404);
    }

    public function getCommentVideoFile($id){
        $comment = ClipComment::findOrFail($id);
        $video = Video::where('videoable_id', $comment->id)->where('videoable_type', 'clip_comment')->first();
        if (!empty($video->file)) {
            $filePath = ClipComment::VIDEO_PATH . $comment->id . '/' . $video->file;
            if (Storage::exists('public/'. $filePath)) {
                return response()->file(storage_path('app/public/' . $filePath));
            }
        }
        return response()->json(['error' => 'File not found'], 404);
    }
}