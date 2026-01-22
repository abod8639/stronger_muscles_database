<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'imageUrl' => $this->image_url,
            'icon' => $this->icon,
            'sortOrder' => $this->sort_order,
            'isActive' => $this->is_active,
            'productsCount' => $this->whenCounted('products'),
            'createdAt' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
