<?php

namespace App\Events;

use App\Http\Resources\Api\V1\SupportTicketResource;
use App\Models\SupportTicket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTicketUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SupportTicket $ticket) {}

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
        return 'SupportTicketUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket' => (new SupportTicketResource($this->ticket->loadMissing(['user', 'admin', 'latestMessage'])))->resolve(),
        ];
    }
}
