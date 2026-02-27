<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'order_id' => (string) $this->order_id,
            'product_id' => (string) $this->product_id,
            'product_name' => is_array($this->product_name)
                ? ($this->product_name['ar'] ?? $this->product_name['en'] ?? array_values($this->product_name)[0] ?? 'Unknown Product')
                : ($this->product_name ?? 'Unknown Product'),
            'unit_price' => (float) $this->unit_price,
            'quantity' => (int) $this->quantity,
            'subtotal' => (float) $this->subtotal,
            'image_url' => $this->image_url,
            'selected_flavor' => is_array($this->flavors) ? ($this->flavors[0] ?? null) : (is_string($this->flavors) ? (json_decode($this->flavors, true)[0] ?? null) : null),
            'selected_size' => is_array($this->size) ? ($this->size[0] ?? null) : (is_string($this->size) ? (json_decode($this->size, true)[0] ?? null) : null),
            // Adding camelCase for compatibility if user's Flutter model expects it
            'selectedFlavor' => is_array($this->flavors) ? ($this->flavors[0] ?? null) : (is_string($this->flavors) ? (json_decode($this->flavors, true)[0] ?? null) : null),
            'selectedSize' => is_array($this->size) ? ($this->size[0] ?? null) : (is_string($this->size) ? (json_decode($this->size, true)[0] ?? null) : null),
        ];
    }
}
