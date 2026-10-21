<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'ticket_id' => (int) $this->ticket_id,
            'sender_type' => $this->sender_type,
            'sender_id' => $this->sender_id ? (int) $this->sender_id : null,
            'sender_name' => $this->sender_name,
            'message' => $this->message,
            'attachments' => $this->attachments ?? [],
            'is_internal_note' => (bool) $this->is_internal_note,
            'is_read' => (bool) $this->is_read,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
