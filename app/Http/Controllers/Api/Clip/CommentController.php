<?php

namespace App\Http\Controllers\Api\Clip;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clip\Clip\Comments\CommentRequest;
use App\Http\Resources\Clip\Clip\Comments\CommentCollection;
use App\Http\Resources\Clip\Clip\Comments\CommentResource;
use App\Models\Clip\Clip;
use App\Models\Clip\ClipComment as Comment;
use App\Repositories\Clip\CommentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    protected $commentRepository;

    public function __construct()
    {
        $this->commentRepository = new CommentRepository();
    }

    public function index($clipId, Request $request)
    {
        $search = $request->get('search', '');
        $filters = $request->get('filters', []);
        $orderBy = $request->get('orderBy', []);
        $perPage = $request->get('per_page', 5); // Default to 5 for frontend consistency
        $with = $request->get('with', []);
        $userId = auth()->guard('sanctum')->id();
            $clip = Clip::find($clipId);

    if(!empty($clip->comments_disable)){
            return $this->response(true, ['comments' => new CommentCollection(collect([]))], 'Comments retrieved successfully');
        }
        
            // Add clip_id to filters
    $filters['clip_id'] = $clipId;
        
        // Build the base query
        $query = Comment::query();
        
        // Apply search if provided
        if (!empty($search)) {
            $query->where('message', 'LIKE', '%' . $search . '%');
        }
        
        // Apply filters
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                $query->where($key, $value);
            }
        }
        
        // Apply privacy filters
        if(!empty($clip->private_comments)){
            if(!$userId){
                return $this->response(true, ['comments' => new CommentCollection(collect([]))], 'Comments retrieved successfully');
            }
            $query->where(function($q) use($userId){
                $q->where('user_id', $userId)
                ->orWhereHas('clip', function($q) use($userId){
                    $q->where('user_id', $userId);
                });
            });
        } else {
            if(!$userId){
                $query->where('privacy', 'public');
            } else {
                $query->where(function($q) use($userId){
                    $q->where('privacy', 'public')
                    ->orWhere('user_id', $userId)
                    ->orWhereHas('clip', function($q) use($userId){
                        $q->where('user_id', $userId);
                    });
                });
            }
        }
        
        // Apply ordering
        if (!empty($orderBy)) {
            foreach ($orderBy as $key => $value) {
                $query->orderBy($key, $value);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        // Apply relationships
        if (!empty($with)) {
            $query->with($with);
        }
        
        // Add default relationships for comments
        $query->with(['user', 'video']);
        
        // Paginate the results
        $paginatedComments = $query->paginate($perPage);
        
        $data = [
            'comments' => new CommentCollection($paginatedComments),
        ];

        return $this->response(true, $data, 'Comments retrieved successfully');
    }

    public function show($id)
    {
        $comment = $this->commentRepository->getById($id);

        if (!$comment) {
            return $this->response(false, null, 'Comment not found', 404);
        }

        $data = [
            'comment' => new CommentResource($comment),
        ];

        return $this->response(true, $data, 'Comment retrieved successfully');
    }

    public function store(CommentRequest $request)
    {
        $data = $request->all();
        $video = $request->file('video');
        $comment = $this->commentRepository->store($data, $video);
        $data = [
            'comment' => new CommentResource($comment),
        ];
        return $this->response(true, $data, 'Comment created successfully', 201);
    }

    public function update(CommentRequest $request, $id)
    {
        $comment = $this->commentRepository->getById($id);
        if (!$comment) {
            return $this->response(false, null, 'Comment not found', 404);
        }
        $data = $request->all();
        $comment = $this->commentRepository->save($data, $id);
        $data = [
            'comment' => new CommentResource($comment),
        ];
        return $this->response(true, $data, 'Comment updated successfully');
    }

    public function destroy($id)
    {
        $comment = $this->commentRepository->delete($id);
        if(!empty($comment->video->file)){
            $this->commentRepository->deleteVideo($id, $comment->video->file);
        }
        if (!$comment) {
            return $this->response(false, null, 'Failed to delete comment', 400);
        }
        $data = [
            'comment' => new CommentResource($comment),
        ];
        return $this->response(true, $data, 'Comment deleted successfully');
    }
}
