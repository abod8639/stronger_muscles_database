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
                'id', 'user_id', 'order_date', 'status', 'payment_status',
                'payment_method', 'subtotal', 'shipping_cost', 'discount', 'total_amount',
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string|unique:orders,id',
            'order_items' => 'required|array|min:1',
            'order_items.*.product_id' => 'required|string|exists:products,id',
            'order_items.*.quantity' => 'required|integer|min:1|max:999',
            'order_items.*.selectedFlavor' => 'nullable|string',
            'order_items.*.selectedSize' => 'nullable|string',
            'shippingCost' => 'required|numeric|min:0|max:999999',
            'payment_method' => 'nullable|string|in:cash,card,paypal,stripe',
            'address_id' => 'nullable|string|exists:addresses,id',
            'shipping_address' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
            'phone_number' => 'nullable|string|max:20',
        ]);

        try {
            return DB::transaction(function () use ($request, $validated) {
                $user = $request->user();
                $calculatedSubtotal = 0;
                $orderItemsData = [];
                $now = now();

                // Fetch all products at once with locking
                $productIds = collect($request->order_items)->pluck('product_id')->unique()->toArray();
                $products = \App\Models\Product::whereIn('id', $productIds)
                    ->select(['id', 'name', 'price', 'discount_price', 'image_urls', 'stock_quantity'])
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // Check all items before processing
                foreach ($request->order_items as $item) {
                    if (! isset($products[$item['product_id']])) {
                        throw new \Exception("Product not found: {$item['product_id']}");
                    }

                    $product = $products[$item['product_id']];

                    if ($product->stock_quantity < $item['quantity']) {
                        throw new \Exception("Insufficient stock for: {$product->name}");
                    }
                }

                // Process items
                foreach ($request->order_items as $item) {
                    $product = $products[$item['product_id']];

                    // Security: Use server-side price
                    $unitPrice = ($product->discount_price > 0 && $product->discount_price < $product->price)
                        ? $product->discount_price
                        : $product->price;

                    $lineSubtotal = $unitPrice * $item['quantity'];
                    $calculatedSubtotal += $lineSubtotal;

                    // Deduct stock
                    $product->decrement('stock_quantity', $item['quantity']);

                    // Prepare for bulk insert
                    $orderItemsData[] = [
                        'id' => (string) \Illuminate\Support\Str::uuid(),
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
                $shippingCost = max(0, (float) $validated['shippingCost']);
                $discount = 0; // Validate coupon if table available

                $grandTotal = max(0, $calculatedSubtotal + $shippingCost - $discount);

                // Create Order
                $order = Order::create([
                    'id' => $validated['id'],
                    'user_id' => $user->id,
                    'order_date' => $now,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'payment_method' => $request->payment_method ?? 'cash',
                    'address_id' => $request->address_id,
                    'shipping_address_snapshot' => $request->shipping_address,
                    'subtotal' => $calculatedSubtotal,
                    'shipping_cost' => $shippingCost,
                    'discount' => $discount,
                    'total_amount' => $grandTotal,
                    'notes' => $request->notes,
                ]);

                // Bulk Insert Items
                if (! empty($orderItemsData)) {
                    OrderItem::insert($orderItemsData);
                }

                // Clear user's cart after successful order
                $user->cartItems()->delete();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Order placed successfully',
                    'data' => new OrderResource($order->load('orderItems')),
                ], 201);
            });
        } catch (\Throwable $e) {
            Log::error('Order creation failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
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
