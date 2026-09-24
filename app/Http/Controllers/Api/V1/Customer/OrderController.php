<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Order\StoreOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->query('limit');

        // Optimized query with eager loading and column selection
        $query = $request->user()
            ->orders()
            ->select([
                'id', 
                'user_id', 
                'order_date', 
                'status', 
                'payment_status',
                'payment_method', 
                'subtotal', 
                'shipping_cost', 
                'discount', 
                'total_amount',
            ])
            ->withItems()
            ->latest();

        if ($limit) {
            $orders = $query->take((int)$limit)->get();
        } else {
            $orders = $query->get();
        }

        return OrderResource::collection($orders)->additional([
            'status' => 'success',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request, OrderService $orderService)
    {
        $validated = $request->validated();

        try {
            $order = $orderService->processCheckout($request->user(), $validated);

            // Clear user's cart after successful order
            $request->user()->cartItems()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Order placed successfully',
                'data' => new OrderResource($order->load('orderItems')),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Order creation failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $order = $request->user()
            ->orders()
            ->select([
                'id', 'user_id', 'order_date', 'status', 'payment_status',
                'payment_method', 'address_id', 'shipping_address_snapshot',
                'subtotal', 'shipping_cost', 'discount', 'total_amount', 'notes',
                'tracking_number', 'created_at', 'updated_at',
            ])
            ->withItems()
            ->findOrFail($id);

        return new OrderResource($order);
    }
}
