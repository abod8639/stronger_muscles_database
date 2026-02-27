<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
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
        $limit = $request->query('limit', 20);

        // Optimized query with eager loading and column selection
        $orders = $request->user()
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
            ->latest()
            ->paginate($limit);

        return OrderResource::collection($orders)->additional([
            'status' => 'success',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, \App\Services\OrderService $orderService)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|string|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.selected_flavor' => 'nullable|string',
            'items.*.selected_size' => 'nullable|string',
            'items.*.selectedFlavor' => 'nullable|string',
            'items.*.selectedSize' => 'nullable|string',
            'payment_method' => 'nullable|string|in:cash,card,paypal,stripe',
            'address_id' => 'required|integer|exists:addresses,id',
            'notes' => 'nullable|string|max:500',
        ]);

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
