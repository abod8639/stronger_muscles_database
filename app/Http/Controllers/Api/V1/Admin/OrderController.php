<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Order\UpdateOrderRequest;
use App\Http\Requests\Admin\Order\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Get real-time order alerts and stats for dashboard.
     */
    public function alerts(Request $request)
    {
        $pendingCount = Order::where('status', 'pending')->count();
        $processingCount = Order::where('status', 'processing')->count();
        $recentOrders = Order::with('user')
            ->where('created_at', '>=', now()->subHours(24))
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($order) => [
                'id' => (string) $order->id,
                'customer_name' => $order->user?->name ?? 'عميل غير مسجل',
                'total_amount' => (float) $order->total_amount,
                'status' => $order->status,
                'created_at' => $order->created_at?->toIso8601String(),
                'time_ago' => $order->created_at?->diffForHumans(),
            ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'has_new_alerts' => $pendingCount > 0,
                'pending_count' => $pendingCount,
                'processing_count' => $processingCount,
                'recent_orders' => $recentOrders,
            ],
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->query('limit', 20); // Default pagination limit
        $status = $request->query('status');

        $query = Order::with('user', 'orderItems')->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->paginate($limit)->through(fn ($order) => $this->formatOrder($order));

        return response()->json([
            'status' => 'success',
            'data' => $orders,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $order = Order::with('user', 'orderItems', 'orderItems.product')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatOrder($order),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, string $id, OrderService $orderService)
    {
        $validated = $request->validated();

        $order = $orderService->updateOrderStatus(
            $id,
            $validated['status'],
            $validated['payment_status'] ?? null,
            $validated['tracking_number'] ?? null,
        );

        return response()->json([
            'status' => 'success',
            'data' => $this->formatOrder($order),
        ]);
    }

    /**
     * Update the order status specifically.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, string $id, OrderService $orderService)
    {
        return $this->update($request, $id, $orderService);
    }

    protected function formatOrder(Order $order): array
    {
        return [
            'id' => (string) $order->id,
            'user' => $order->user ? [
                'id' => $order->user->id,
                'name' => $order->user->name,
                'email' => $order->user->email,
            ] : null,
            'order_date' => $order->order_date ? $order->order_date->toIso8601String() : ($order->created_at ? $order->created_at->toIso8601String() : null),
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'address_id' => (string) $order->address_id,
            'subtotal' => (float) $order->subtotal,
            'shippingCost' => (float) $order->shipping_cost,
            'shipping_cost' => (float) $order->shipping_cost,
            'discount' => (float) $order->discount,
            'total_amount' => (float) $order->total_amount,
            'tracking_number' => $order->tracking_number,
            'notes' => $order->notes,
            'shipping_address' => $order->shipping_address_snapshot,
            'order_items' => $order->orderItems->map(fn ($item) => [
                'id' => (string) $item->id,
                'order_id' => (string) $item->order_id,
                'product_id' => (string) $item->product_id,
                'product_name' => is_array($item->product_name)
                    ? ($item->product_name['ar'] ?? $item->product_name['en'] ?? array_values($item->product_name)[0] ?? 'Unknown Product')
                    : ($item->product_name ?? 'Unknown Product'),
                'unit_price' => (float) $item->unit_price,
                'quantity' => (int) $item->quantity,
                'subtotal' => (float) $item->subtotal,
                'image_url' => $item->image_url,
                'selectedFlavor' => is_array($item->flavors) ? ($item->flavors[0] ?? null) : null,
                'selectedSize' => is_array($item->size) ? ($item->size[0] ?? null) : null,
                'selected_flavor' => is_array($item->flavors) ? ($item->flavors[0] ?? null) : null,
                'selected_size' => is_array($item->size) ? ($item->size[0] ?? null) : null,
            ]),
            'createdAt' => $order->created_at?->toIso8601String(),
            'updatedAt' => $order->updated_at?->toIso8601String(),
        ];
    }
}
