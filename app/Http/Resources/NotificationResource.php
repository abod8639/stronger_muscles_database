<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isRead = isset($this->is_read)
            ? (bool) $this->is_read
            : ($user instanceof \App\Models\User ? $this->isReadBy($user) : false);

        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type ?? 'general',
            'target_type' => $this->target_type ?? 'all',
            'user_id' => $this->user_id ? (string) $this->user_id : null,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => (string) $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'admin' => $this->whenLoaded('admin', fn () => [
                'id' => (string) $this->admin->id,
                'name' => $this->admin->name,
            ]),
            'data' => $this->data,
            'image_url' => $this->image_url,
            'is_read' => $isRead,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
