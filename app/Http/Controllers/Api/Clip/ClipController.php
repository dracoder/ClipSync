<?php

namespace App\Http\Controllers\Api\Clip;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clip\Clips\ClipRequest;
use App\Http\Resources\Clip\Clips\ClipCollection;
use App\Http\Resources\Clip\Clips\ClipResource;
use App\Models\Clip\Clip;
use App\Models\Clip\ClipView;
use App\Repositories\Clip\ClipRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ClipController extends Controller
{
    protected $clipRepository;

    public function __construct()
    {
        $this->clipRepository = new ClipRepository();
    }

    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $filters = $request->get('filters', []);
        $orderBy = $request->get('orderBy', []);
        $perPage = $request->get('per_page', null);
        $with = $request->get('with', []);
        $user = Auth::user();

        $clips = $this->clipRepository->getList($search, $filters, $orderBy, $perPage, $with, 'custom');
        $clips->where('user_id', $user->id);
        $clips->whereHas('video', function($query){
            $query->where('file', '!=', null);
        });
        
        $data = [
            'clips' => new ClipCollection($clips->get()),
        ];

        return $this->response(true, $data, __('messages.model_action.retrive_success', ['attribute' => 'Clips']));
    }

    public function show($slug)
    {
        $clip = $this->clipRepository->getById($slug, [], 'slug');

        if (!$clip) {
            return $this->response(false, null, 'Clip not found', 404);
        }

        $data = [
            'clip' => new ClipResource($clip),
        ];

        return $this->response(true, $data, __('messages.model_action.retrive_success', ['attribute' => 'Clip']));
    }

    public function store(ClipRequest $request)
    {
        $data = $request->all();
        $video = $request->file('video');
        $data['user_id'] = $request->user()->id;
        $clip = $this->clipRepository->store($data, $video);
        $this->clipRepository->setSlug($clip);
        $data = [
            'clip' => new ClipResource($clip),
        ];
        return $this->response(true, $data, __('messages.model_action.create_success', ['attribute' => 'Clip']), 201);
    }

    public function update(ClipRequest $request, $id)
    {
        $clip = $this->clipRepository->getById($id);

        if (!$clip) {
            return $this->response(false, null, 'Clip not found', 404);
        }

        $data = $request->all();
        $video = $request->file('video');
        $clip = $this->clipRepository->update($data, $id, $video);
        $data = [
            'clip' => new ClipResource($clip),
        ];
        return $this->response(true, $data, __('messages.model_action.update_success', ['attribute' => 'Clip']));
    }

    public function destroy($id)
    {
        $clip = $this->clipRepository->getById($id);
        
        if (!$clip) {
            return $this->response(false, null, 'Clip not found', 404);
        }

        // Store clip data before deletion
        $clipData = $clip->toArray();
        $videoData = $clip->video;

        // Delete video files
        if (!empty($videoData)) {
            try {
                $this->clipRepository->deleteAllVideoFiles($id, $videoData);
            } catch (\Exception $e) {
                Log::error('Error deleting video files for clip: ' . $e->getMessage(), [
                    'clip_id' => $id,
                    'exception' => $e
                ]);
            }
        }

        $deletedClip = $this->clipRepository->delete($id);
        
        if (!$deletedClip) {
            return $this->response(false, null,  __('messages.model_action.delete_error', ['attribute' => 'Clip']), 400);
        }
        
        $data = [
            'clip' => new ClipResource($deletedClip),
        ];
        return $this->response(true, $data, __('messages.model_action.delete_success', ['attribute' => 'Clip']));
    }

    public function trackViewer(Request $request){
        $data = $request->validate([
            'clip_id' => 'required|exists:clips,id',
            'user_id' => 'nullable|exists:users,id',
        ]);
        $data['ip_address'] = $request->ip();

        ClipView::create($data);
        
        return response()->json(['message' => 'viewer_added']);
    }
} 