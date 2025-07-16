<?php

namespace App\Repositories\Studio;

use App\Models\Studio\Room;
use Illuminate\Support\Str;
use App\Models\Studio\RoomConfiguration;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Hash;

class RoomRepository extends BaseRepository
{
    protected $roomConfigurationModel;

    public function __construct()
    {
        $this->model = new Room();
        $this->roomConfigurationModel = new RoomConfiguration();
    }

    public function getRoomBySlug($slug, $relations = [])
    {
        try {
            return $this->model->with($relations)->where('slug', $slug)->first();
        } catch (\Exception $exception) {
            log_error($exception);
            return false;
        }
    }

    public function saveConfig($data, $roomId = null)
    {
        // try {
            $configurationData = [
                'restrict_guest' => $data['restrict_guest'],
                'guest_password' => $data['guest_password'],
                'admin_password' => $data['admin_password'],
            ];

            if (isset($data) && is_array($data)) {
                foreach ($data as $key => $value) {
                    if($key != 'restrict_guest' && $key != 'guest_password' && $key != 'restrict_admin' && $key != 'admin_password') {
                     $configurationData['configuration'][$key] = $value;
                    }
                }
            }

            if (isset($data['mainBgImage']) && $data['mainBgImage']) {
                $bgImageUrl = $this->saveFile($data['mainBgImage']);
                if ($bgImageUrl) {
                    $configurationData['configuration']['mainBgImage'] = $bgImageUrl;
                }
            }

            if (isset($data['logoSrc']) && $data['logoSrc']) {
                $logoUrl = $this->saveFile($data['logoSrc']);
                if ($logoUrl) {
                    $configurationData['configuration']['logoSrc'] = $logoUrl; 
                }
            }

            $config = $this->roomConfigurationModel->where('room_id', $roomId)->first();
            $configurationData['restrict_guest'] = $configurationData['restrict_guest'] == "true" ? 1 : 0;

            if ($config) {
                $config->update([
                    'configuration' => $configurationData['configuration'],
                    'guest_password' => $configurationData['guest_password'] != null ? Hash::make($configurationData['guest_password']) : null,
                    'admin_password' => $configurationData['admin_password'] != null ? Hash::make($configurationData['admin_password']) : null,
                    'restrict_guest' => $configurationData['restrict_guest'],
                ]);
            } else {
                $config =  $this->roomConfigurationModel->create([
                    'room_id' => $roomId,
                    'configuration' => $configurationData['configuration'],
                    'guest_password' => $configurationData['guest_password'] != null ? Hash::make($configurationData['guest_password']) : null,
                    'admin_password' => $configurationData['admin_password'] != null ? Hash::make($configurationData['admin_password']) : null,
                    'restrict_guest' => $configurationData['restrict_guest'],
                ]);
            }

            return $config;
        // } catch (\Exception $exception) {
        //     log_error($exception);
        //     return false;
        // }

    }

    public function getRoomConfig($roomId)
    {
        try {

            $config = $this->roomConfigurationModel->where('room_id', $roomId)->first();
            return $config;
        } catch (\Exception $exception) {
            log_error($exception);
            return false;
        }
    }

    public function roomAuthorization($config, $password , $isAdmin = false)
    {
        try {
            if($isAdmin)
            {
                if ($config->admin_password) {
                    if (Hash::check($password, $config->admin_password)) {
                        return true;
                    }
                }
                return false;
            }else{
                if ($config->restrict_guest) {
                    if ($config->guest_password) {
                        if (Hash::check($password, $config->guest_password)) {
                            return true;
                        }
                    }
                    return false;
                }
                return true;
            }
        } catch (\Exception $exception) {
            log_error($exception);
            return false;
        }
    }

    public function saveFile($file, $folder = 'uploads')
    {
        try {
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                if ($file && $file->isValid()) {
                    $extension = $file->getClientOriginalExtension();
                    
                    $fileName = Str::random(10) . '_' . time() . '.' . $extension;
                    $filePath = $file->storeAs('public/' . $folder, $fileName);
                    
                    $fileUrl = asset(str_replace('public/', 'storage/', $filePath));
                    
                    
                    return $fileUrl;
                }
            }
            return null;  
        } catch (\Exception $exception) {
            log_error($exception);
            return null; 
        }
    }
}
