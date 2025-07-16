<?php

namespace App\Http\Resources\Clip\Clip\Comments;

use App\Http\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CommentCollection extends ResourceCollection
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
                'data' => CommentResource::collection($this->collection),
                'pagination' => new PaginationResource($this->resource),
            ];
        } else {
            return [
                'data' => CommentResource::collection($this->collection)
            ];
        }
    }
}
