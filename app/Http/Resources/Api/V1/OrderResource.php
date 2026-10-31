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
        $shippingAddress = $this->shipping_address_snapshot;
        if (!is_array($shippingAddress) && !empty($shippingAddress)) {
            $decoded = json_decode($shippingAddress, true);
            if (is_array($decoded)) {
                $shippingAddress = $decoded;
            }
        }
        if (empty($shippingAddress) && $this->address_id) {
            $dbAddress = \App\Models\Address::find($this->address_id);
            $shippingAddress = $dbAddress ? $dbAddress->toArray() : null;
        } elseif (is_array($shippingAddress) && (empty($shippingAddress['latitude']) || empty($shippingAddress['longitude'])) && $this->address_id) {
            $dbAddress = \App\Models\Address::find($this->address_id);
            if ($dbAddress && $dbAddress->latitude && $dbAddress->longitude) {
                $shippingAddress['latitude'] = (float) $dbAddress->latitude;
                $shippingAddress['longitude'] = (float) $dbAddress->longitude;
                if (empty($shippingAddress['street']) || $shippingAddress['street'] === '1600 Amphitheatre Pkwy') {
                    $shippingAddress['street'] = $dbAddress->street;
                }
            }
        }

        return [
            'id' => (string) $this->id,
            'user_id' => (string) $this->user_id,
            'order_date' => $this->order_date ? $this->order_date->toIso8601String() : ($this->created_at ? $this->created_at->toIso8601String() : null),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'address_id' => (string) $this->address_id,
            'subtotal' => (float) $this->subtotal,
            'shippingCost' => (float) $this->shipping_cost,
            'taxAmount' => (float) $this->tax_amount,
            'discount' => (float) $this->discount,
            'total_amount' => (float) $this->total_amount,
            'tracking_number' => $this->tracking_number,
            'phone_number' => $this->phone_number,
            'notes' => $this->notes,
            'shipping_address' => is_array($shippingAddress) ? $shippingAddress : null,
            'order_items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
