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
            'product_name' => $this->product_name ?? 'Unknown Product',
            'unit_price' => (double) $this->unit_price,
            'quantity' => (int) $this->quantity,
            'subtotal' => (double) $this->subtotal,
            'image_url' => $this->image_url,
            'selectedFlavor' => $this->flavors[0] ?? null, // Assuming single flavor for now based on array cast logic
            'selectedSize' => $this->size[0] ?? null,
        ];
    }
}
