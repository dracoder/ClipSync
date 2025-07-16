<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Clip\ClipRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ClipStreamController extends Controller
{
    protected $clipRepository;

    public function __construct(ClipRepository $clipRepository)
    {
        $this->clipRepository = $clipRepository;
    }

    /**
     * Upload dual video streams and create clip
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadStreams(Request $request): JsonResponse
    {
        try {
            // Debug logging for uploaded files
            if ($request->hasFile('webcam_video')) {
                $webcamFile = $request->file('webcam_video');
                Log::info('Webcam video upload details', [
                    'original_name' => $webcamFile->getClientOriginalName(),
                    'mime_type' => $webcamFile->getMimeType(),
                    'extension' => $webcamFile->getClientOriginalExtension(),
                    'size' => $webcamFile->getSize()
                ]);
            }
            
            if ($request->hasFile('screen_video')) {
                $screenFile = $request->file('screen_video');
                Log::info('Screen video upload details', [
                    'original_name' => $screenFile->getClientOriginalName(),
                    'mime_type' => $screenFile->getMimeType(),
                    'extension' => $screenFile->getClientOriginalExtension(),
                    'size' => $screenFile->getSize()
                ]);
            }
            
            // Validate the request - more lenient file validation
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'nullable|string',
                'webcam_video' => 'nullable|file|max:100000', // 100MB max - basic file validation only
                'screen_video' => 'nullable|file|max:100000', // 100MB max - basic file validation only
                'metadata' => 'nullable|string' // Changed from json to string for broader compatibility
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Ensure at least one video is provided
            if (!$request->hasFile('webcam_video') && !$request->hasFile('screen_video')) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one video stream is required'
                ], 422);
            }

            // Additional file type validation for video files (accept common video extensions even with octet-stream MIME)
            if ($request->hasFile('webcam_video')) {
                $webcamFile = $request->file('webcam_video');
                $webcamMimeType = $webcamFile->getMimeType();
                $webcamExtension = strtolower($webcamFile->getClientOriginalExtension());
                $validVideoExtensions = ['webm', 'mp4', 'mov', 'avi'];
                
                if (!str_starts_with($webcamMimeType, 'video/') && 
                    $webcamMimeType !== 'application/octet-stream' && 
                    !in_array($webcamExtension, $validVideoExtensions)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Webcam video must be a valid video file',
                        'debug' => ['mime_type' => $webcamMimeType, 'extension' => $webcamExtension]
                    ], 422);
                }
            }

            if ($request->hasFile('screen_video')) {
                $screenFile = $request->file('screen_video');
                $screenMimeType = $screenFile->getMimeType();
                $screenExtension = strtolower($screenFile->getClientOriginalExtension());
                $validVideoExtensions = ['webm', 'mp4', 'mov', 'avi'];
                
                if (!str_starts_with($screenMimeType, 'video/') && 
                    $screenMimeType !== 'application/octet-stream' && 
                    !in_array($screenExtension, $validVideoExtensions)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Screen video must be a valid video file',
                        'debug' => ['mime_type' => $screenMimeType, 'extension' => $screenExtension]
                    ], 422);
                }
            }

            // Parse metadata
            $metadata = [];
            if ($request->has('metadata')) {
                $metadata = json_decode($request->input('metadata'), true) ?? [];
            }

            // Prepare clip data
            $clipData = [
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'user_id' => auth()->id()
            ];

            // Get video files
            $webcamVideo = $request->file('webcam_video');
            $screenVideo = $request->file('screen_video');

            Log::info('Processing dual stream upload', [
                'user_id' => auth()->id(),
                'title' => $request->input('title'),
                'has_webcam' => $webcamVideo !== null,
                'has_screen' => $screenVideo !== null,
                'metadata' => $metadata
            ]);

            // Create clip with dual streams
            $clip = $this->clipRepository->storeWithDualStreams(
                $clipData,
                $webcamVideo,
                $screenVideo,
                $metadata
            );

            // Set slug after creation
            $this->clipRepository->setSlug($clip);

            return response()->json([
                'success' => true,
                'message' => 'Clip created successfully with merged video streams',
                'data' => [
                    'clip_id' => $clip->id,
                    'slug' => $clip->slug,
                    'title' => $clip->title,
                    'processing_status' => 'completed'
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error in dual stream upload: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process video streams: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get processing status of a clip
     *
     * @param int $clipId
     * @return JsonResponse
     */
    public function getProcessingStatus(int $clipId): JsonResponse
    {
        try {
            $clip = $this->clipRepository->findOrFail($clipId);
            
            // Check if user owns the clip
            if ($clip->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $video = $clip->video;
            
            if (!$video) {
                return response()->json([
                    'success' => false,
                    'message' => 'No video found for this clip'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'clip_id' => $clip->id,
                    'processing_status' => $video->processing_status ?? 'completed',
                    'stream_type' => $video->stream_type ?? 'single',
                    'has_webcam_stream' => !empty($video->webcam_stream_path),
                    'has_screen_stream' => !empty($video->screen_stream_path),
                    'created_at' => $clip->created_at,
                    'updated_at' => $clip->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting processing status: ' . $e->getMessage(), [
                'clip_id' => $clipId,
                'user_id' => auth()->id(),
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get processing status'
            ], 500);
        }
    }

    /**
     * Get stream metadata for a clip
     *
     * @param int $clipId
     * @return JsonResponse
     */
    public function getStreamMetadata(int $clipId): JsonResponse
    {
        try {
            $clip = $this->clipRepository->findOrFail($clipId);
            
            // Check if user owns the clip or clip is public
            if ($clip->user_id !== auth()->id() && $clip->privacy !== 'public') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $video = $clip->video;
            
            if (!$video) {
                return response()->json([
                    'success' => false,
                    'message' => 'No video found for this clip'
                ], 404);
            }

            $metadata = [];
            if ($video->stream_metadata) {
                $metadata = json_decode($video->stream_metadata, true) ?? [];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'clip_id' => $clip->id,
                    'stream_type' => $video->stream_type ?? 'single',
                    'processing_status' => $video->processing_status ?? 'completed',
                    'metadata' => $metadata,
                    'video_file' => $video->file
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting stream metadata: ' . $e->getMessage(), [
                'clip_id' => $clipId,
                'user_id' => auth()->id(),
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get stream metadata'
            ], 500);
        }
    }
}
