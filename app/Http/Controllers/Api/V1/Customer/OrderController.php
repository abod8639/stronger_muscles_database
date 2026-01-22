<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\Api\V1\OrderResource;
use Illuminate\Support\Facades\Log;

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
            ->paginate($limit);

        return OrderResource::collection($orders)->additional([
            'status' => 'success',
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
            'phone_number' => 'nullable|string',
            // 'discount' is removed from validation to prevent user input
        ]);

        try {
            return DB::transaction(function () use ($request, $validated) {
                $user = $request->user();
                $calculatedSubtotal = 0;
                $orderItemsData = [];
                $now = now();

                foreach ($request->order_items as $item) {
                    // Race Conditions: Lock the product row
                    $product = \App\Models\Product::where('id', $item['product_id'])->lockForUpdate()->first();

                    // Inventory Management: Check stock
                    if ($product->stock_quantity < $item['quantity']) {
                        throw new \Exception("Insufficient stock for product: {$product->name}");
                    }

                    // Security: Use server-side price
                    $unitPrice = ($product->discount_price > 0 && $product->discount_price < $product->price)
                        ? $product->discount_price
                        : $product->price;

                    $lineSubtotal = $unitPrice * $item['quantity'];
                    $calculatedSubtotal += $lineSubtotal;

                    // Inventory Management: Deduct stock
                    $product->stock_quantity -= $item['quantity'];
                    $product->save();

                    // Optimize: Prepare for bulk insert
                    // Note: insert() does not trigger casts, so we must manually encode arrays
                    $orderItemsData[] = [
                        'id' => $item['id'] ?? (string) \Illuminate\Support\Str::uuid(),
                        'order_id' => $validated['id'],
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'unit_price' => $unitPrice,
                        'quantity' => $item['quantity'],
                        'subtotal' => $lineSubtotal,
                        'image_url' => $product->image_urls[0] ?? null,
                        'flavors' => json_encode(isset($item['selectedFlavor']) ? [$item['selectedFlavor']] : []),
                        'size' => json_encode(isset($item['selectedSize']) ? [$item['selectedSize']] : []),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // Final Calculation
                $shippingCost = $validated['shippingCost'];

                // Security: Calculate discount server-side (e.g., validate coupon)
                // For now, defaulting to 0 as no trusted coupon logic exists yet.
                // Re-enable/Implement coupon logic here when table is available.
                $discount = 0;

                $grandTotal = $calculatedSubtotal + $shippingCost - $discount;

                // Create Order
                $order = Order::create([
                    'id' => $validated['id'],
                    'user_id' => $user->id,
                    'order_date' => $now,
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

                // Optimize: Bulk Insert Items
                if (!empty($orderItemsData)) {
                    OrderItem::insert($orderItemsData);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Order placed successfully',
                    'data' => new OrderResource($order->load('orderItems'))
                ], 201);
            });
        } catch (\Throwable $e) {
            Log::error('Order creation failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $order = $request->user()->orders()->with(['orderItems', 'orderItems.product'])->findOrFail($id);

        return new OrderResource($order);
    }
}



