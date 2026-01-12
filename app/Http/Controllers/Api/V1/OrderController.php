<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        // If authenticated, get user's orders. If not (Dashboard context), get all orders.
        if ($request->user()) {
            $orders = $request->user()->orders()->with('orderItems')->latest()->get();
        } else {
            $orders = Order::with('orderItems')->latest()->get();
        }
        
        return response()->json($orders);
    }

    /**
     * Store a newly created order (Simplified for now).
     */
    public function store(Request $request)
    {
        return response()->json(['message' => 'Order processing not implemented yet'], 501);
    }
    protected function formatOrder(Order $order): array
    {
        return [
            'id' => (string) $order->id,
            'user_id' => (string) $order->user_id,
            'order_date' => $order->order_date ? $order->order_date->toIso8601String() : $order->created_at->toIso8601String(),
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'address_id' => (string) $order->address_id,
            'subtotal' => (double) $order->subtotal,
            'shippingCost' => (double) $order->shipping_cost, // تحويل للاسم المتوقع في Flutter
            'discount' => (double) $order->discount,
            'total_amount' => (double) $order->total_amount,
            'tracking_number' => $order->tracking_number,
            'notes' => $order->notes,
            'shipping_address' => $order->shipping_address, // التأكد من مطابقة الاسم
            'order_items' => $order->orderItems->map(fn($item) => [
                'id' => (string) $item->id,
                'order_id' => (string) $item->order_id,
                'product_id' => (string) $item->product_id,
                'product_name' => $item->product_name,
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
    /**
     * Display the specified order.
     */
    public function show(Request $request, string $id)
    {
        if ($request->user()) {
            $order = $request->user()->orders()->with('orderItems.product')->findOrFail($id);
        } else {
            $order = Order::with('orderItems.product')->findOrFail($id);
        }
        
        return response()->json($order);
    }
}
