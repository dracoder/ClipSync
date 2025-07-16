<?php

namespace App\Repositories\Clip;

use App\Models\Clip\Clip;
use App\Models\Clip\Video;
use App\Repositories\BaseRepository;
use App\Services\VideoMergeService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class ClipRepository extends BaseRepository
{
    protected $videoMergeService;

    public function __construct()
    {
        $this->model = new Clip();
        $this->videoMergeService = app(VideoMergeService::class);
    }

    public function store($data, $video = null)
    {
        try {
            $clip = $this->model->create($data);
            
            if ($video && $video instanceof UploadedFile) {
                $this->storeVideo($video, $clip->id);
            }
            
            return $clip;
        } catch (\Exception $exception) {
            Log::error('ClipRepository::store failed', [
                'exception' => $exception->getMessage(),
                'data' => $data
            ]);
            return false;
        }
    }

    public function update($data, $id, $video = null)
    {
        try {
            $clip = $this->model->find($id);
            if (!$clip) {
                return false;
            }

            $clip->update($data);

            if ($video && $video instanceof UploadedFile) {
                // Delete old video if exists
                $oldVideo = $clip->video;
                if ($oldVideo && $oldVideo->file) {
                    $this->deleteVideoFile($oldVideo->file);
                    $oldVideo->delete();
                }
                
                $this->storeVideo($video, $clip->id);
            }

            return $clip;
        } catch (\Exception $exception) {
            Log::error('ClipRepository::update failed', [
                'exception' => $exception->getMessage(),
                'clip_id' => $id
            ]);
            return false;
        }
    }

    public function storeVideo($video, $clipId)
    {
        try {
            $fileName = time() . '_' . Str::random(10) . '.' . $video->getClientOriginalExtension();
            $filePath = $video->storeAs('public/' . Clip::VIDEO_PATH, $fileName);
            
            Video::create([
                'videoable_id' => $clipId,
                'videoable_type' => 'clip',
                'file' => $fileName,
                'file_path' => $filePath
            ]);

            return true;
        } catch (\Exception $exception) {
            Log::error('ClipRepository::storeVideo failed', [
                'exception' => $exception->getMessage(),
                'clip_id' => $clipId
            ]);
            return false;
        }
    }

    public function storeWithDualStreams($clipData, $webcamVideo = null, $screenVideo = null, $metadata = [])
    {
        try {
            Log::info('ClipRepository::storeWithDualStreams called', [
                'has_webcam' => $webcamVideo !== null,
                'has_screen' => $screenVideo !== null,
                'metadata' => $metadata
            ]);

            // Create the clip first
            $clip = $this->model->create($clipData);

            // Store dual stream video
            $this->uploadDualStreams($webcamVideo, $screenVideo, $clip->id, $metadata);

            return $clip;
        } catch (\Exception $exception) {
            Log::error('ClipRepository::storeWithDualStreams failed', [
                'exception' => $exception->getMessage(),
                'clip_data' => $clipData
            ]);
            throw $exception;
        }
    }

    public function uploadDualStreams($webcamVideo = null, $screenVideo = null, $clipId, $metadata = [])
    {
        try {
            Log::info('Processing dual streams for clip', [
                'clip_id' => $clipId,
                'has_webcam' => $webcamVideo !== null,
                'has_screen' => $screenVideo !== null
            ]);

            // Validate that we have at least one video
            if (!$webcamVideo && !$screenVideo) {
                throw new \Exception('At least one video stream is required');
            }

            // Store the initial video record
            $videoRecord = Video::create([
                'videoable_id' => $clipId,
                'videoable_type' => 'clip',
                'stream_type' => ($webcamVideo && $screenVideo) ? 'dual' : 'single',
                'processing_status' => 'processing',
                'stream_metadata' => json_encode($metadata)
            ]);

            // Save individual stream files
            $webcamPath = null;
            $screenPath = null;

            if ($webcamVideo) {
                $webcamPath = $this->saveStreamFile($webcamVideo, 'webcam', $clipId);
                $videoRecord->update(['webcam_stream_path' => $webcamPath]);
            }

            if ($screenVideo) {
                $screenPath = $this->saveStreamFile($screenVideo, 'screen', $clipId);
                $videoRecord->update(['screen_stream_path' => $screenPath]);
            }

            // Process the video (merge if dual stream)
            if ($webcamVideo && $screenVideo) {
                // Dual stream - merge using VideoMergeService
                $mergedPath = $this->videoMergeService->mergeStreams(
                    storage_path('app/' . $webcamPath),
                    storage_path('app/' . $screenPath),
                    $metadata
                );

                if ($mergedPath) {
                    $finalFileName = basename($mergedPath);
                    $videoRecord->update([
                        'file' => $finalFileName,
                        'file_path' => 'public/' . Clip::VIDEO_PATH . $finalFileName,
                        'processing_status' => 'completed'
                    ]);
                } else {
                    throw new \Exception('Failed to merge video streams');
                }
            } else {
                // Single stream - copy to final location
                $sourceFile = $webcamVideo ?: $screenVideo;
                $finalFileName = time() . '_' . Str::random(10) . '.' . $sourceFile->getClientOriginalExtension();
                $finalPath = $sourceFile->storeAs('public/' . Clip::VIDEO_PATH, $finalFileName);
                
                $videoRecord->update([
                    'file' => $finalFileName,
                    'file_path' => $finalPath,
                    'processing_status' => 'completed'
                ]);
            }

            Log::info('Dual stream processing completed', ['clip_id' => $clipId]);
            return true;

        } catch (\Exception $exception) {
            Log::error('ClipRepository::uploadDualStreams failed', [
                'exception' => $exception->getMessage(),
                'clip_id' => $clipId
            ]);
            throw $exception;
        }
    }

    private function saveStreamFile($file, $type, $clipId)
    {
        $fileName = $type . '_' . $clipId . '_' . time() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('temp/streams', $fileName);
    }

    public function setSlug($clip)
    {
        $slug = Str::slug($clip->title ?? 'clip-' . $clip->id);
        $originalSlug = $slug;
        $counter = 1;

        while (Clip::where('slug', $slug)->where('id', '!=', $clip->id)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $clip->update(['slug' => $slug]);
        return $clip;
    }

    public function findOrFail($id)
    {
        return $this->model->findOrFail($id);
    }

    public function deleteAllVideoFiles($clipId, $videoData)
    {
        try {
            // Delete main video file
            if (!empty($videoData->file)) {
                $this->deleteVideoFile($videoData->file);
            }

            // Delete stream files if they exist
            if (!empty($videoData->webcam_stream_path)) {
                Storage::delete($videoData->webcam_stream_path);
            }

            if (!empty($videoData->screen_stream_path)) {
                Storage::delete($videoData->screen_stream_path);
            }

            // Delete video record
            $videoData->delete();

            return true;
        } catch (\Exception $exception) {
            Log::error('Failed to delete video files', [
                'clip_id' => $clipId,
                'exception' => $exception->getMessage()
            ]);
            return false;
        }
    }

    private function deleteVideoFile($fileName)
    {
        try {
            $filePath = 'public/' . Clip::VIDEO_PATH . $fileName;
            Storage::delete($filePath);
            return true;
        } catch (\Exception $exception) {
            Log::error('Failed to delete video file', [
                'file_name' => $fileName,
                'exception' => $exception->getMessage()
            ]);
            return false;
        }
    }
} 