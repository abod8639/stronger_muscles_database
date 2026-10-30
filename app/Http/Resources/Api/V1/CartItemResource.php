<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->product;

        return [
            'id' => (string) $this->id,
            'user_id' => (string) $this->user_id,
            'product_id' => (string) $this->product_id,
            'product_name' => (string) $this->product_name,
            'price' => (float) $this->price,
            'image_urls' => $this->image_urls,
            'quantity' => (int) $this->quantity,
            'flavors' => $this->flavors ?? [],
            'size' => $this->size ?? [],
            'selected_flavor' => is_array($this->flavors) ? ($this->flavors[0] ?? null) : $this->flavors,
            'selected_size' => is_array($this->size) ? ($this->size[0] ?? null) : $this->size,
            'flavor' => is_array($this->flavors) ? ($this->flavors[0] ?? null) : $this->flavors,
            'added_at' => $this->added_at?->toIso8601String() ?? (is_string($this->added_at) ? $this->added_at : null),
            'total_price' => (float) $this->getTotalPriceAttribute(),
            'product' => $product ? (new ProductResource($product))->resolve() : null,
        ];
    }
}

