<?php

namespace App\Services;

use App\Events\SupportMessageSent;
use App\Events\SupportTicketCreated;
use App\Events\SupportTicketUpdated;
use App\Models\Admin;
use App\Models\Notification;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Repositories\SupportTicketRepository;
use App\Services\Notification\PushNotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SupportTicketService
{
    public function __construct(
        protected SupportTicketRepository $ticketRepository,
        protected PushNotificationService $pushNotificationService
    ) {}

    /**
     * Get admin tickets with filters.
     */
    public function getAdminTickets(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->ticketRepository->getAdminTickets($filters, $perPage);
    }

    /**
     * Get customer tickets.
     */
    public function getCustomerTickets(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->ticketRepository->getCustomerTickets($userId, $perPage);
    }

    /**
     * Find ticket by ID for admin.
     */
    public function getAdminTicket(int $id): ?SupportTicket
    {
        $ticket = $this->ticketRepository->findById($id, includeInternalNotes: true);
        if ($ticket) {
            $this->ticketRepository->markMessagesAsRead($ticket, 'admin');
        }

        return $ticket;
    }

    /**
     * Find ticket by ID for customer.
     */
    public function getCustomerTicket(int $id, int $userId): ?SupportTicket
    {
        $ticket = $this->ticketRepository->findById($id, includeInternalNotes: false);

        if (! $ticket || $ticket->user_id !== $userId) {
            return null;
        }

        $this->ticketRepository->markMessagesAsRead($ticket, 'user');

        return $ticket;
    }

    /**
     * Create a ticket from customer or guest.
     */
    public function createTicket(array $data, ?User $user = null): SupportTicket
    {
        $payload = [
            'ticket_number' => 'SM-'.strtoupper(Str::random(8)),
            'user_id' => $user?->id ?? ($data['user_id'] ?? null),
            'name' => $data['name'] ?? ($user?->name ?? 'Guest User'),
            'email' => $data['email'] ?? ($user?->email ?? ''),
            'phone' => $data['phone'] ?? ($user?->phone_number ?? null),
            'subject' => $data['subject'],
            'category' => $data['category'] ?? 'general',
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'open',
            'order_id' => $data['order_id'] ?? null,
            'last_reply_at' => now(),
        ];

        $ticket = $this->ticketRepository->createTicket($payload);

        // Add initial message if provided
        if (! empty($data['message'])) {
            $this->ticketRepository->addMessage($ticket, [
                'sender_type' => 'user',
                'sender_id' => $user?->id,
                'sender_name' => $ticket->name,
                'message' => $data['message'],
                'attachments' => $data['attachments'] ?? null,
                'is_internal_note' => false,
                'is_read' => false,
            ]);
        }

        // Broadcast WebSocket event
        try {
            SupportTicketCreated::dispatch($ticket);
        } catch (\Throwable $e) {
            Log::warning("WebSocket broadcast failed for SupportTicketCreated: {$e->getMessage()}");
        }

        // Sync to Firebase for realtime dashboard listening
        $this->syncToFirebase($ticket);

        return $ticket->load(['user', 'latestMessage', 'messages']);
    }

    /**
     * Customer adds message to ticket.
     */
    public function addCustomerMessage(SupportTicket $ticket, string $message, ?array $attachments = null, ?User $user = null): SupportMessage
    {
        $supportMessage = $this->ticketRepository->addMessage($ticket, [
            'sender_type' => 'user',
            'sender_id' => $user?->id,
            'sender_name' => $user?->name ?? $ticket->name,
            'message' => $message,
            'attachments' => $attachments,
            'is_internal_note' => false,
            'is_read' => false,
        ]);

        // If ticket was waiting for user or resolved, change status back to open/in_progress
        if (in_array($ticket->status, ['waiting_user', 'resolved', 'closed'])) {
            $this->ticketRepository->updateStatus($ticket, 'in_progress');
        }

        // Broadcast WebSocket event
        try {
            SupportMessageSent::dispatch($ticket, $supportMessage);
        } catch (\Throwable $e) {
            Log::warning("WebSocket broadcast failed for SupportMessageSent: {$e->getMessage()}");
        }

        // Sync to Firebase
        $this->syncToFirebase($ticket, $supportMessage);

        return $supportMessage;
    }

    /**
     * Admin replies to ticket or adds internal note.
     */
    public function addAdminMessage(
        SupportTicket $ticket,
        string $message,
        ?Admin $admin,
        bool $isInternalNote = false,
        ?array $attachments = null,
        ?string $newStatus = null
    ): SupportMessage {
        $supportMessage = $this->ticketRepository->addMessage($ticket, [
            'sender_type' => 'admin',
            'sender_id' => $admin?->id,
            'sender_name' => $admin?->name ?? 'الدعم الفني',
            'message' => $message,
            'attachments' => $attachments,
            'is_internal_note' => $isInternalNote,
            'is_read' => false,
        ]);

        if (! $isInternalNote) {
            // Update ticket status
            $status = $newStatus ?? 'waiting_user';
            $this->ticketRepository->updateStatus($ticket, $status);

            // Notify user via Push Notification & In-app Notification
            $this->notifyUserOfReply($ticket, $message, $admin);
        }

        // Broadcast WebSocket event
        try {
            SupportMessageSent::dispatch($ticket, $supportMessage);
        } catch (\Throwable $e) {
            Log::warning("WebSocket broadcast failed for SupportMessageSent: {$e->getMessage()}");
        }

        // Sync to Firebase
        $this->syncToFirebase($ticket, $supportMessage);

        return $supportMessage;
    }

    /**
     * Update ticket status and notify user if resolved.
     */
    public function updateStatus(SupportTicket $ticket, string $status, ?Admin $admin = null): SupportTicket
    {
        $updatedTicket = $this->ticketRepository->updateStatus($ticket, $status);

        if ($status === 'resolved' && $ticket->user) {
            $this->pushNotificationService->sendToUser(
                $ticket->user,
                'تم حل تذكرة الدعم',
                "تم حل تذكرتك بنجاح (#{$ticket->ticket_number}): {$ticket->subject}",
                [
                    'type' => 'support_ticket',
                    'ticket_id' => (string) $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'status' => 'resolved',
                ]
            );

            Notification::create([
                'title' => "تم حل تذكرة الدعم (#{$ticket->ticket_number})",
                'body' => "قام فريق الدعم الفني بحل تذكرتك بنجاح: {$ticket->subject}",
                'type' => 'support',
                'target_type' => 'user',
                'user_id' => $ticket->user_id,
                'data' => [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'status' => 'resolved',
                ],
                'sent_by' => $admin?->id,
            ]);
        }

        // Broadcast WebSocket event
        try {
            SupportTicketUpdated::dispatch($updatedTicket);
        } catch (\Throwable $e) {
            Log::warning("WebSocket broadcast failed for SupportTicketUpdated: {$e->getMessage()}");
        }

        $this->syncToFirebase($updatedTicket);

        return $updatedTicket;
    }

    /**
     * Update ticket priority.
     */
    public function updatePriority(SupportTicket $ticket, string $priority): SupportTicket
    {
        $updatedTicket = $this->ticketRepository->updatePriority($ticket, $priority);

        // Broadcast WebSocket event
        try {
            SupportTicketUpdated::dispatch($updatedTicket);
        } catch (\Throwable $e) {
            Log::warning("WebSocket broadcast failed for SupportTicketUpdated: {$e->getMessage()}");
        }

        $this->syncToFirebase($updatedTicket);

        return $updatedTicket;
    }

    /**
     * Assign ticket to an admin.
     */
    public function assignAdmin(SupportTicket $ticket, ?int $adminId): SupportTicket
    {
        $updatedTicket = $this->ticketRepository->assignAdmin($ticket, $adminId);

        // Broadcast WebSocket event
        try {
            SupportTicketUpdated::dispatch($updatedTicket);
        } catch (\Throwable $e) {
            Log::warning("WebSocket broadcast failed for SupportTicketUpdated: {$e->getMessage()}");
        }

        $this->syncToFirebase($updatedTicket);

        return $updatedTicket;
    }

    /**
     * Delete ticket.
     */
    public function deleteTicket(SupportTicket $ticket): bool
    {
        return $this->ticketRepository->delete($ticket);
    }

    /**
     * Get aggregate statistics.
     */
    public function getStats(): array
    {
        return $this->ticketRepository->getStats();
    }

    /**
     * Notify customer about admin response.
     */
    protected function notifyUserOfReply(SupportTicket $ticket, string $message, ?Admin $admin): void
    {
        if (! $ticket->user) {
            return;
        }

        $title = "رد جديد على تذكرة الدعم (#{$ticket->ticket_number})";
        $body = Str::limit($message, 120);

        // 1. Send Firebase Cloud Messaging push notification
        $this->pushNotificationService->sendToUser(
            $ticket->user,
            $title,
            $body,
            [
                'type' => 'support_ticket',
                'ticket_id' => (string) $ticket->id,
                'ticket_number' => $ticket->ticket_number,
            ]
        );

        // 2. Create in-app notification in database
        Notification::create([
            'title' => $title,
            'body' => $body,
            'type' => 'support',
            'target_type' => 'user',
            'user_id' => $ticket->user_id,
            'data' => [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
            ],
            'sent_by' => $admin?->id,
        ]);
    }

    /**
     * Synchronize ticket and messages to Firebase Realtime Database if configured.
     */
    protected function syncToFirebase(SupportTicket $ticket, ?SupportMessage $newMessage = null): void
    {
        $projectId = config('services.fcm.project_id') ?? 'stronger-muscles';
        $rtdbUrl = config('services.firebase.database_url') ?? "https://{$projectId}-default-rtdb.firebaseio.com";

        try {
            $ticketData = [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'user_id' => $ticket->user_id,
                'name' => $ticket->name,
                'email' => $ticket->email,
                'subject' => $ticket->subject,
                'category' => $ticket->category,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'admin_id' => $ticket->admin_id,
                'last_reply_at' => $ticket->last_reply_at?->toISOString(),
                'updated_at' => now()->toISOString(),
            ];

            // Send async HTTP request to Firebase Realtime DB (fails gracefully if credentials or internet are not configured)
            Http::timeout(2)->patch("{$rtdbUrl}/tickets/{$ticket->id}.json", $ticketData);

            if ($newMessage && ! $newMessage->is_internal_note) {
                $messageData = [
                    'id' => $newMessage->id,
                    'ticket_id' => $ticket->id,
                    'sender_type' => $newMessage->sender_type,
                    'sender_name' => $newMessage->sender_name,
                    'message' => $newMessage->message,
                    'created_at' => $newMessage->created_at?->toISOString() ?? now()->toISOString(),
                ];
                Http::timeout(2)->post("{$rtdbUrl}/tickets/{$ticket->id}/messages.json", $messageData);
            }
        } catch (\Throwable $e) {
            // Non-blocking: log and continue seamlessly
            Log::debug('Firebase RTDB sync skipped or offline: '.$e->getMessage());
        }
    }
}
