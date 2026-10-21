<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketDetailResource extends JsonResource
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
            'ticket_number' => $this->ticket_number,
            'user_id' => $this->user_id ? (int) $this->user_id : null,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'subject' => $this->subject,
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => $this->status,
            'order_id' => $this->order_id ? (int) $this->order_id : null,
            'admin_id' => $this->admin_id ? (int) $this->admin_id : null,
            'assigned_admin' => $this->whenLoaded('admin', function () {
                return $this->admin ? [
                    'id' => $this->admin->id,
                    'name' => $this->admin->name,
                    'email' => $this->admin->email,
                    'role' => $this->admin->role,
                ] : null;
            }),
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'phone_number' => $this->user->phone_number,
                    'photo_url' => $this->user->photo_url,
                ] : null;
            }),
            'order' => $this->whenLoaded('order', function () {
                return $this->order ? [
                    'id' => $this->order->id,
                    'order_number' => $this->order->order_number ?? (string) $this->order->id,
                    'status' => $this->order->status,
                    'total_amount' => (float) $this->order->total_amount,
                ] : null;
            }),
            'messages' => SupportMessageResource::collection($this->whenLoaded('messages')),
            'last_reply_at' => $this->last_reply_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
