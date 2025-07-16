<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaginationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (method_exists($this->resource, 'currentPage')) {
            $pagination  = [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
                'total_pages' => ceil($this->total() / $this->perPage()),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ];
        } else {
            $pagination = [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => -1,
                'total' => $this->count(),
                'total_pages' => 1,
                'from' => 1,
                'to' => $this->count(),
            ];
        }
        return $pagination;
    }
}
