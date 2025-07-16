<?php

namespace App\Http\Resources\Studio\Rooms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomConfigurationResource extends JsonResource
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
            'room_id' => $this->room_id,
            'restrict_guest' => $this->restrict_guest,
            'restrict_admin' => $this->admin_password != null ? true : false,
            'configuration' => $this->configuration,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
