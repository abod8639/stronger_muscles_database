<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
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

        $orders = $query->paginate($limit)->through(fn($order) => $this->formatOrder($order));

        return response()->json([
            'status' => 'success',
            'data' => $orders
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
            'data' => $this->formatOrder($order)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'payment_status' => 'nullable|string',
            'tracking_number' => 'nullable|string',
        ]);

        $order->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatOrder($order)
        ]);
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
            'order_date' => $order->order_date ? $order->order_date->toIso8601String() : $order->created_at->toIso8601String(),
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'address_id' => (string) $order->address_id,
            'subtotal' => (double) $order->subtotal,
            'shippingCost' => (double) $order->shipping_cost,
            'discount' => (double) $order->discount,
            'total_amount' => (double) $order->total_amount,
            'tracking_number' => $order->tracking_number,
            'notes' => $order->notes,
            'shipping_address' => $order->shipping_address_snapshot,
            'order_items' => $order->orderItems->map(fn($item) => [
                'id' => (string) $item->id,
                'order_id' => (string) $item->order_id,
                'product_id' => (string) $item->product_id,
                'product_name' => $item->product_name ?? 'Unknown Product',
                'unit_price' => (double) $item->unit_price,
                'quantity' => (int) $item->quantity,
                'subtotal' => (double) $item->subtotal,
                'image_url' => $item->image_url,
                'selectedFlavor' => $item->selected_flavor,
                'selectedSize' => $item->selected_size,
            ]),
            'createdAt' => $order->created_at->toIso8601String(),
            'updatedAt' => $order->updated_at->toIso8601String(),
        ];
    }
}
