<?php

namespace App\Repositories\Studio;

use App\Models\Studio\RoomChat;
use App\Repositories\BaseRepository;
use App\Repositories\Studio\RoomRepository; 

class ChatRepository extends BaseRepository
{
    protected $roomRepository;

    public function __construct()
    {
        $this->model = new RoomChat();
        $this->roomRepository = new RoomRepository(); 
    }

    public function getList($search = '', $filters = [], $orderBy = [], $perPage = null, $with = [], $slug = '')
    {
        $room = $this->roomRepository->getRoomBySlug($slug);

        if (!$room) {
            return [];
        }

        if (!method_exists($this, 'getQuery')) {
            return [];
        }

        $list = $this->getQuery($search, $filters)->where('room_id', $room->id);

        if (!empty($with)) {
            $list = $list->with($with);
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $key => $value) {
                $list->orderBy($key, $value);
            }
        } else {
            $list->orderBy('created_at', 'desc');
        }

        if ($perPage == -1) {
            $list = $list->get();
        } else {
            if ($perPage) {
                $list = $list->paginate($perPage);
            } else {
                $list = $list->paginate(config('settings.pagination.per_page', 10));
            }
        }

        return $list;
    }

    public function saveByRoomSlug($data, $slug = '')
    {
        try {
            $room = $this->roomRepository->getRoomBySlug($slug);
            $data['room_id']= $room->id;  
            return $this->model->create($data);
        } catch (\Exception $exception) {
            log_error($exception);
            return false;
        }
    }

    public function deleteByRoomSlug($slug)
    {
        try {
            $room = $this->roomRepository->getRoomBySlug($slug);
            $model = $this->model->where('room_id',$room->id)->delete();
            return true;
        } catch (\Exception $exception) {
            log_error($exception);
            return false;
        }
    }
}
