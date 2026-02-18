<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'sku' => (string) $this->sku,
            'price' => (float) $this->price,
            'discount_price' => $this->discount_price ? (float) $this->discount_price : null,
            'effective_price' => (float) $this->effective_price,
            'stock_quantity' => (int) $this->stock_quantity,
            'attributes' => $this->attributes, // {"size": "1kg", "flavor": "Vanilla"}
            'is_active' => (bool) $this->is_active,
            'discount_start_date' => $this->discount_start_date?->toIso8601String(),
            'discount_end_date' => $this->discount_end_date?->toIso8601String(),
        ];
    }
}
