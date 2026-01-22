<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'user_id' => (string) $this->user_id,
            'order_date' => $this->order_date ? $this->order_date->toIso8601String() : $this->created_at->toIso8601String(),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'address_id' => (string) $this->address_id,
            'subtotal' => (double) $this->subtotal,
            'shippingCost' => (double) $this->shipping_cost,
            'discount' => (double) $this->discount,
            'total_amount' => (double) $this->total_amount,
            'tracking_number' => $this->tracking_number,
            'phone_number' => $this->phone_number,
            'notes' => $this->notes,
            'shipping_address' => $this->shipping_address_snapshot,
            'order_items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
