<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone_number,
            'photo_url' => $this->photo_url,
            'default_address_id' => $this->default_address_id,
            'preferred_language' => $this->preferred_language ?? 'ar',
            'notifications_enabled' => (bool) ($this->notifications_enabled ?? true),
            'is_active' => (bool) ($this->is_active ?? true),
            'role' => $this->role ?? 'user',
            'total_spent' => (double) ($this->total_spent ?? 0),
            'orders_count' => (int) ($this->orders_count ?? 0),
            'addresses' => AddressResource::collection($this->whenLoaded('addresses')),
            'created_at' => $this->created_at?->toIso8601String(),
            'last_login' => $this->last_login?->toIso8601String(),
        ];
    }
}
