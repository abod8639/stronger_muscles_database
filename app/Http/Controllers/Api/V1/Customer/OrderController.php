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
            'id' => 'required|string|unique:orders,id',
            'order_items' => 'required|array',
            'order_items.*.product_id' => 'required|exists:products,id',
            'order_items.*.quantity' => 'required|integer|min:1',
            'shippingCost' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'address_id' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'phone_number' => 'nullable|string', // Validated to ensure it's captured
            'discount' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $user = $request->user();
            $calculatedSubtotal = 0;
            $orderItemsData = [];

            foreach ($request->order_items as $item) {
                // Race Conditions: Lock the product row
                $product = \App\Models\Product::where('id', $item['product_id'])->lockForUpdate()->first();

                // Inventory Management: Check stock
                if ($product->stock_quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                // Security: Use server-side price
                // Prefer discount_price if it is active (e.g. > 0 and < price)
                $unitPrice = ($product->discount_price > 0 && $product->discount_price < $product->price)
                    ? $product->discount_price
                    : $product->price;

                $lineSubtotal = $unitPrice * $item['quantity'];
                $calculatedSubtotal += $lineSubtotal;

                // Inventory Management: Deduct stock
                $product->stock_quantity -= $item['quantity'];
                $product->save();

                $orderItemsData[] = [
                    'id' => $item['id'] ?? (string) \Illuminate\Support\Str::uuid(),
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $unitPrice,
                    'quantity' => $item['quantity'],
                    'subtotal' => $lineSubtotal,
                    'image_url' => $product->image_urls[0] ?? null,
                    'flavors' => isset($item['selectedFlavor']) ? [$item['selectedFlavor']] : [],
                    'size' => isset($item['selectedSize']) ? [$item['selectedSize']] : [],
                ];
            }

            // Final Calculation
            $shippingCost = $validated['shippingCost'];
            $discount = $request->discount ?? 0;
            $grandTotal = $calculatedSubtotal + $shippingCost - $discount;

            // Create Order
            $order = Order::create([
                'id' => $validated['id'],
                'user_id' => $user->id,
                'order_date' => now(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $request->payment_method ?? 'cash',
                'address_id' => $request->address_id,
                'shipping_address_snapshot' => $request->shipping_address ?? '',
                'subtotal' => $calculatedSubtotal,
                'shipping_cost' => $shippingCost,
                'discount' => $discount,
                'total_amount' => $grandTotal,
                'notes' => $validated['notes'],
                'phone_number' => $request->phone_number,
            ]);

            // Create Order Items
            foreach ($orderItemsData as $itemData) {
                $itemData['order_id'] = $order->id;
                OrderItem::create($itemData);
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
            'phone_number' => $order->phone_number,
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
