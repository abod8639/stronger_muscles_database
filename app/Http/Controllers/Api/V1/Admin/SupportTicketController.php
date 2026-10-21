<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\AssignTicketAdminRequest;
use App\Http\Requests\Support\StoreTicketMessageRequest;
use App\Http\Requests\Support\UpdateTicketPriorityRequest;
use App\Http\Requests\Support\UpdateTicketStatusRequest;
use App\Http\Resources\Api\V1\SupportMessageResource;
use App\Http\Resources\Api\V1\SupportTicketDetailResource;
use App\Http\Resources\Api\V1\SupportTicketResource;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupportTicketController extends Controller
{
    public function __construct(
        protected SupportTicketService $ticketService
    ) {}

    /**
     * Display a listing of tickets with filters and statistics for the dashboard.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [
            'status' => $request->query('status'),
            'priority' => $request->query('priority'),
            'category' => $request->query('category'),
            'admin_id' => $request->query('admin_id'),
            'search' => $request->query('search'),
        ];

        $perPage = (int) $request->query('per_page', 15);
        $tickets = $this->ticketService->getAdminTickets($filters, $perPage);

        return SupportTicketResource::collection($tickets)->additional([
            'status' => 'success',
            'stats' => $this->ticketService->getStats(),
        ]);
    }

    /**
     * Get aggregate statistics for tickets and complaints.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->ticketService->getStats(),
        ]);
    }

    /**
     * Display the specified ticket details and full messages thread.
     */
    public function show(int $id): JsonResponse
    {
        $ticket = $this->ticketService->getAdminTicket($id);

        if (! $ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'التذكرة غير موجودة',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new SupportTicketDetailResource($ticket),
        ]);
    }

    /**
     * Admin replies to customer or adds an internal note.
     */
    public function addMessage(int $id, StoreTicketMessageRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->getAdminTicket($id);

        if (! $ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'التذكرة غير موجودة',
            ], 404);
        }

        $admin = $request->user();
        $isInternalNote = (bool) $request->input('is_internal_note', false);
        $newStatus = $request->input('status');

        $message = $this->ticketService->addAdminMessage(
            $ticket,
            $request->validated('message'),
            $admin,
            $isInternalNote,
            $request->validated('attachments'),
            $newStatus
        );

        return response()->json([
            'status' => 'success',
            'message' => $isInternalNote ? 'تم إضافة الملاحظة الداخلية بنجاح' : 'تم إرسال الرد للعميل وإشعاره بنجاح',
            'data' => new SupportMessageResource($message),
        ], 201);
    }

    /**
     * Update ticket status.
     */
    public function updateStatus(int $id, UpdateTicketStatusRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->getAdminTicket($id);

        if (! $ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'التذكرة غير موجودة',
            ], 404);
        }

        $updated = $this->ticketService->updateStatus(
            $ticket,
            $request->validated('status'),
            $request->user()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث حالة التذكرة بنجاح',
            'data' => new SupportTicketResource($updated),
        ]);
    }

    /**
     * Update ticket priority.
     */
    public function updatePriority(int $id, UpdateTicketPriorityRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->getAdminTicket($id);

        if (! $ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'التذكرة غير موجودة',
            ], 404);
        }

        $updated = $this->ticketService->updatePriority(
            $ticket,
            $request->validated('priority')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم تعديل أولوية التذكرة بنجاح',
            'data' => new SupportTicketResource($updated),
        ]);
    }

    /**
     * Assign ticket to an admin/agent.
     */
    public function assign(int $id, AssignTicketAdminRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->getAdminTicket($id);

        if (! $ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'التذكرة غير موجودة',
            ], 404);
        }

        $updated = $this->ticketService->assignAdmin(
            $ticket,
            $request->validated('admin_id')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم تعيين المشرف المسؤول بنجاح',
            'data' => new SupportTicketResource($updated),
        ]);
    }

    /**
     * Delete the specified ticket.
     */
    public function destroy(int $id): JsonResponse
    {
        $ticket = $this->ticketService->getAdminTicket($id);

        if (! $ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'التذكرة غير موجودة',
            ], 404);
        }

        $this->ticketService->deleteTicket($ticket);

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف التذكرة بنجاح',
        ]);
    }
}
