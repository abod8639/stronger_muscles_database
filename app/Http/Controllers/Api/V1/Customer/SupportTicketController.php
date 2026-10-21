<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreTicketMessageRequest;
use App\Http\Requests\Support\StoreTicketRequest;
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
     * Display a listing of tickets for the authenticated customer.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 15);
        $user = $request->user();

        $tickets = $this->ticketService->getCustomerTickets($user->id, $perPage);

        return SupportTicketResource::collection($tickets)->additional([
            'status' => 'success',
        ]);
    }

    /**
     * Store a newly created ticket by the customer.
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $user = $request->user();
        $ticket = $this->ticketService->createTicket($request->validated(), $user);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إنشاء تذكرة الدعم بنجاح وسيتم الرد عليك في أقرب وقت',
            'data' => new SupportTicketDetailResource($ticket),
        ], 201);
    }

    /**
     * Display the specified ticket with conversation thread.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $ticket = $this->ticketService->getCustomerTicket($id, $user->id);

        if (! $ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'التذكرة غير موجودة أو ليس لديك صلاحية للوصول إليها',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new SupportTicketDetailResource($ticket),
        ]);
    }

    /**
     * Add a message/reply to an existing ticket.
     */
    public function addMessage(int $id, StoreTicketMessageRequest $request): JsonResponse
    {
        $user = $request->user();
        $ticket = $this->ticketService->getCustomerTicket($id, $user->id);

        if (! $ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'التذكرة غير موجودة أو ليس لديك صلاحية للوصول إليها',
            ], 404);
        }

        $message = $this->ticketService->addCustomerMessage(
            $ticket,
            $request->validated('message'),
            $request->validated('attachments'),
            $user
        );

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال رسالتك بنجاح',
            'data' => new SupportMessageResource($message),
        ], 201);
    }

    /**
     * Close the ticket by the customer.
     */
    public function close(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $ticket = $this->ticketService->getCustomerTicket($id, $user->id);

        if (! $ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'التذكرة غير موجودة أو ليس لديك صلاحية للوصول إليها',
            ], 404);
        }

        $updated = $this->ticketService->updateStatus($ticket, 'closed');

        return response()->json([
            'status' => 'success',
            'message' => 'تم إغلاق التذكرة بنجاح',
            'data' => new SupportTicketResource($updated),
        ]);
    }
}
