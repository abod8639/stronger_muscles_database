<?php

namespace App\Events;

use App\Http\Resources\Api\V1\SupportMessageResource;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public SupportMessage $message
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('support.tickets'),
            new Channel('support.ticket.'.$this->ticket->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'SupportMessageSent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket_id' => (int) $this->ticket->id,
            'message' => (new SupportMessageResource($this->message))->resolve(),
        ];
    }
}
