<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Address;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function processCheckout($user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {
            $calculatedSubtotal = 0;
            $orderItemsData = [];
            $now = now();
            $orderId = (string) Str::uuid();

            // Fetch all products at once with locking
            $productIds = collect($data['items'])->pluck('product_id')->unique()->toArray();
            $products = Product::whereIn('id', $productIds)
                ->select(['id', 'name', 'price', 'discount_price', 'image_urls', 'stock_quantity', 'product_sizes'])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Check all items before processing
            foreach ($data['items'] as $item) {
                if (!isset($products[$item['product_id']])) {
                    throw new \Exception("Product not found: {$item['product_id']}");
                }

                $product = $products[$item['product_id']];

                if ($product->stock_quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for: {$product->name}");
                }
            }

            // Process items
            foreach ($data['items'] as $item) {
                $product = $products[$item['product_id']];

                $selectedSize = $item['selected_size'] ?? $item['selectedSize'] ?? null;
                $selectedFlavor = $item['selected_flavor'] ?? $item['selectedFlavor'] ?? null;

                $unitPrice = $this->determineUnitPrice($product, $selectedSize);

                $lineSubtotal = $unitPrice * $item['quantity'];
                $calculatedSubtotal += $lineSubtotal;

                // Deduct stock
                $product->decrement('stock_quantity', $item['quantity']);

                // Extract proper image URL string
                $imageUrl = null;
                if (isset($product->image_urls[0])) {
                    $img = $product->image_urls[0];
                    $imageUrl = is_array($img) ? ($img['medium'] ?? $img['original'] ?? null) : $img;
                }

                // Prepare for Eloquent create
                $orderItemsData[] = [
                    'id' => (string) Str::uuid(),
                    'product_id' => $product->id,
                    'product_name' => $product->name, // Pass array, Eloquent will cast
                    'unit_price' => $unitPrice,
                    'quantity' => $item['quantity'],
                    'subtotal' => $lineSubtotal,
                    'image_url' => $imageUrl,
                    'flavors' => $selectedFlavor ? [$selectedFlavor] : [],
                    'size' => $selectedSize ? [$selectedSize] : [],
                ];
            }

            // Final Calculation
            $shippingCost = 50.0; // Hardcoded or fetching from config
            $discount = 0; // Validate coupon if table available

            $grandTotal = max(0, $calculatedSubtotal + $shippingCost - $discount);

            // Fetch Address if address_id provided
            $shippingAddressSnapshot = null;
            if (isset($data['address_id'])) {
                $address = Address::find($data['address_id']);
                if ($address) {
                    $shippingAddressSnapshot = $address->toArray();
                }
            }

            $orderData = [
                'id' => $orderId,
                'user_id' => $user->id,
                'order_date' => $now,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $data['payment_method'] ?? 'cash',
                'address_id' => $data['address_id'] ?? null,
                'shipping_address_snapshot' => $shippingAddressSnapshot,
                'subtotal' => $calculatedSubtotal,
                'shipping_cost' => $shippingCost,
                'discount' => $discount,
                'total_amount' => $grandTotal,
                'notes' => $data['notes'] ?? null,
            ];

            return $this->orderRepository->createOrderWithItems($orderData, $orderItemsData);
        });
    }

    private function determineUnitPrice(Product $product, ?string $sizeName): float
    {
        // If a specific size is requested, try to find its price
        if ($sizeName && is_array($product->product_sizes)) {
            foreach ($product->product_sizes as $sizeObj) {
                // Check 'size' or 'name' property for the size identifier
                $currentSizeName = $sizeObj['size'] ?? $sizeObj['name'] ?? null;
                if ($currentSizeName === $sizeName) {
                    // Order of preference: discount_price, effectivePrice, price
                    if (isset($sizeObj['discount_price']) && (float) $sizeObj['discount_price'] > 0) {
                        return (float) $sizeObj['discount_price'];
                    }
                    if (isset($sizeObj['effectivePrice']) && (float) $sizeObj['effectivePrice'] > 0) {
                        return (float) $sizeObj['effectivePrice'];
                    }
                    if (isset($sizeObj['price']) && (float) $sizeObj['price'] > 0) {
                        return (float) $sizeObj['price'];
                    }
                }
            }
        }

        // Fallback to base product price if size is not found or has no size-specific price
        $basePrice = ($product->discount_price > 0 && $product->discount_price < $product->price)
            ? (float) $product->discount_price
            : (float) $product->price;
            
        return $basePrice;
    }
}
