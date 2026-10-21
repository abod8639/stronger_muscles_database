<?php

namespace App\Repositories;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupportTicketRepository
{
    /**
     * Get paginated tickets for admin with filters and eager loading.
     */
    public function getAdminTickets(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return SupportTicket::query()
            ->filter($filters)
            ->with([
                'user:id,name,email,photo_url,phone_number',
                'admin:id,name,email,role',
                'latestMessage',
            ])
            ->withCount([
                'messages',
                'messages as unread_messages_count' => function ($query) {
                    $query->where('sender_type', 'user')->where('is_read', false);
                },
            ])
            ->latest('last_reply_at')
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * Get paginated tickets for a customer.
     */
    public function getCustomerTickets(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return SupportTicket::query()
            ->where('user_id', $userId)
            ->with([
                'latestMessage',
            ])
            ->withCount([
                'messages',
                'messages as unread_messages_count' => function ($query) {
                    $query->where('sender_type', 'admin')->where('is_read', false);
                },
            ])
            ->latest('last_reply_at')
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * Find ticket by ID with messages.
     */
    public function findById(int $id, bool $includeInternalNotes = false): ?SupportTicket
    {
        return SupportTicket::query()
            ->with([
                'user:id,name,email,photo_url,phone_number',
                'admin:id,name,email,role',
                'order:id,order_number,status,total_amount,created_at',
                'messages' => function ($query) use ($includeInternalNotes) {
                    if (! $includeInternalNotes) {
                        $query->where('is_internal_note', false);
                    }
                    $query->oldest('created_at');
                },
            ])
            ->find($id);
    }

    /**
     * Find ticket by ticket number.
     */
    public function findByTicketNumber(string $ticketNumber, bool $includeInternalNotes = false): ?SupportTicket
    {
        return SupportTicket::query()
            ->where('ticket_number', $ticketNumber)
            ->with([
                'user:id,name,email,photo_url,phone_number',
                'admin:id,name,email,role',
                'order:id,order_number,status,total_amount,created_at',
                'messages' => function ($query) use ($includeInternalNotes) {
                    if (! $includeInternalNotes) {
                        $query->where('is_internal_note', false);
                    }
                    $query->oldest('created_at');
                },
            ])
            ->first();
    }

    /**
     * Create a new support ticket.
     */
    public function createTicket(array $data): SupportTicket
    {
        return SupportTicket::create($data);
    }

    /**
     * Add a message to a ticket.
     */
    public function addMessage(SupportTicket $ticket, array $messageData): SupportMessage
    {
        $message = $ticket->messages()->create($messageData);

        $ticket->update([
            'last_reply_at' => now(),
        ]);

        return $message;
    }

    /**
     * Update ticket status.
     */
    public function updateStatus(SupportTicket $ticket, string $status): SupportTicket
    {
        $attributes = ['status' => $status];

        if ($status === 'resolved' && ! $ticket->resolved_at) {
            $attributes['resolved_at'] = now();
        } elseif ($status === 'closed' && ! $ticket->closed_at) {
            $attributes['closed_at'] = now();
        }

        $ticket->update($attributes);

        return $ticket->fresh(['user', 'admin', 'latestMessage']);
    }

    /**
     * Update ticket priority.
     */
    public function updatePriority(SupportTicket $ticket, string $priority): SupportTicket
    {
        $ticket->update(['priority' => $priority]);

        return $ticket->fresh(['user', 'admin', 'latestMessage']);
    }

    /**
     * Assign ticket to an admin.
     */
    public function assignAdmin(SupportTicket $ticket, ?int $adminId): SupportTicket
    {
        $ticket->update(['admin_id' => $adminId]);

        return $ticket->fresh(['user', 'admin', 'latestMessage']);
    }

    /**
     * Mark messages in a ticket as read for a specific recipient type.
     */
    public function markMessagesAsRead(SupportTicket $ticket, string $readBy = 'admin'): void
    {
        if ($readBy === 'admin') {
            $ticket->messages()->where('sender_type', 'user')->where('is_read', false)->update(['is_read' => true]);
        } else {
            $ticket->messages()->where('sender_type', 'admin')->where('is_read', false)->update(['is_read' => true]);
        }
    }

    /**
     * Delete a support ticket.
     */
    public function delete(SupportTicket $ticket): bool
    {
        return (bool) $ticket->delete();
    }

    /**
     * Get aggregate statistics for tickets.
     */
    public function getStats(): array
    {
        $total = SupportTicket::count();
        $open = SupportTicket::where('status', 'open')->count();
        $inProgress = SupportTicket::where('status', 'in_progress')->count();
        $waitingUser = SupportTicket::where('status', 'waiting_user')->count();
        $resolved = SupportTicket::where('status', 'resolved')->count();
        $closed = SupportTicket::where('status', 'closed')->count();
        $urgent = SupportTicket::where('priority', 'urgent')->whereIn('status', ['open', 'in_progress'])->count();
        $resolvedToday = SupportTicket::where('status', 'resolved')->whereDate('resolved_at', today())->count();

        $byCategory = SupportTicket::selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        $byPriority = SupportTicket::selectRaw('priority, count(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        return [
            'total' => $total,
            'open' => $open,
            'in_progress' => $inProgress,
            'waiting_user' => $waitingUser,
            'resolved' => $resolved,
            'closed' => $closed,
            'urgent' => $urgent,
            'resolved_today' => $resolvedToday,
            'by_category' => $byCategory,
            'by_priority' => $byPriority,
        ];
    }
}
