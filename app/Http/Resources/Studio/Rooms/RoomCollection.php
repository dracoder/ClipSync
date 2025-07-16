<?php

namespace App\Http\Resources\Studio\Rooms;

use App\Http\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RoomCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        // if has pagination then send pagination data else return just resource
        if (method_exists($this->resource, 'currentPage')) {
            return [
                'data' => RoomResource::collection($this->collection),
                'pagination' => new PaginationResource($this->resource),
            ];
        } else {
            return [
                'data' => RoomResource::collection($this->collection)
            ];
        }
    }
}
