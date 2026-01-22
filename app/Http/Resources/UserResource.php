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
            'role' => $this->role,
            'addresses' => $this->whenLoaded('addresses'),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
