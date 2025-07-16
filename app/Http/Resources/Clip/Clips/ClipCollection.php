<?php

namespace App\Http\Resources\Clip\Clip\Clips;

use App\Http\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ClipCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (method_exists($this->resource, 'currentPage')) {
            return [
                'data' => ClipResource::collection($this->collection),
                'pagination' => new PaginationResource($this->resource),
            ];
        } else {
            return [
                'data' => ClipResource::collection($this->collection)
            ];
        }
    }
}
