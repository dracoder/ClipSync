<?php

namespace App\Repositories\Clip;

use App\Models\Clip\ClipComment;
use App\Models\Clip\Video;
use App\Repositories\BaseRepository;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class CommentRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new ClipComment();
    }

    public function store($data, $video = null)
    {
        try {
            $comment = $this->model->create($data);
            
            if ($video && $video instanceof UploadedFile) {
                $this->storeVideo($video, $comment->id);
            }
            
            return $comment;
        } catch (\Exception $exception) {
            Log::error('CommentRepository::store failed', [
                'exception' => $exception->getMessage(),
                'data' => $data
            ]);
            return false;
        }
    }

    public function save($data, $id = null)
    {
        try {
            if ($id) {
                $comment = $this->model->find($id);
                if (!$comment) {
                    return false;
                }
                $comment->update($data);
                return $comment;
            } else {
                return $this->store($data);
            }
        } catch (\Exception $exception) {
            Log::error('CommentRepository::save failed', [
                'exception' => $exception->getMessage(),
                'comment_id' => $id
            ]);
            return false;
        }
    }

    public function delete($id)
    {
        try {
            $comment = $this->model->find($id);
            if ($comment) {
                // Delete associated video if exists
                if ($comment->video) {
                    $this->deleteVideo($id, $comment->video->file);
                }
                $comment->delete();
            }
            return $comment;
        } catch (\Exception $exception) {
            Log::error('CommentRepository::delete failed', [
                'exception' => $exception->getMessage(),
                'comment_id' => $id
            ]);
            return false;
        }
    }

    private function storeVideo($video, $commentId)
    {
        try {
            $fileName = time() . '_comment_' . Str::random(10) . '.' . $video->getClientOriginalExtension();
            $filePath = $video->storeAs('public/comment-videos/', $fileName);
            
            Video::create([
                'videoable_id' => $commentId,
                'videoable_type' => 'clip_comment',
                'file' => $fileName,
                'file_path' => $filePath
            ]);

            return true;
        } catch (\Exception $exception) {
            Log::error('CommentRepository::storeVideo failed', [
                'exception' => $exception->getMessage(),
                'comment_id' => $commentId
            ]);
            return false;
        }
    }

    public function deleteVideo($commentId, $fileName)
    {
        try {
            if ($fileName) {
                $filePath = 'public/comment-videos/' . $fileName;
                Storage::delete($filePath);
            }

            // Delete video record
            Video::where('videoable_id', $commentId)
                 ->where('videoable_type', 'clip_comment')
                 ->delete();

            return true;
        } catch (\Exception $exception) {
            Log::error('CommentRepository::deleteVideo failed', [
                'exception' => $exception->getMessage(),
                'comment_id' => $commentId,
                'file_name' => $fileName
            ]);
            return false;
        }
    }
} 