<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->query('limit', 20);
        $orders = $request->user()
            ->orders()
            ->with(['orderItems', 'orderItems.product'])
            ->latest()
            ->paginate($limit)
            ->through(fn($order) => $this->formatOrder($order));

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string',
            'order_items' => 'required|array',
            'subtotal' => 'required|numeric',
            'shippingCost' => 'required|numeric',
            'total_amount' => 'required|numeric',
            'shipping_address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $user = $request->user();

            $order = Order::create([
                'id' => $validated['id'],
                'user_id' => $user->id,
                'order_date' => now(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $request->payment_method ?? 'cash',
                'address_id' => $request->address_id,
                'shipping_address_snapshot' => $request->shipping_address ?? '',
                'subtotal' => $validated['subtotal'],
                'shipping_cost' => $validated['shippingCost'],
                'discount' => $request->discount ?? 0,
                'total_amount' => $validated['total_amount'],
                'notes' => $validated['notes'],
            ]);

            foreach ($request->order_items as $item) {
                OrderItem::create([
                    'id' => $item['id'],
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                    'image_url' => $item['image_url'] ?? null,
                    'flavors' => isset($item['selectedFlavor']) ? [$item['selectedFlavor']] : [],
                    'size' => isset($item['selectedSize']) ? [$item['selectedSize']] : [],
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Order placed successfully',
                'data' => $this->formatOrder($order->load('orderItems'))
            ], 201);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $order = $request->user()->orders()->with(['orderItems', 'orderItems.product'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatOrder($order)
        ]);
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
