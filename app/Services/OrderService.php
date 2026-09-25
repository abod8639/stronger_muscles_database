<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Product;
use App\Repositories\OrderRepository;
use App\Services\Notification\PushNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected PushNotificationService $notificationService,
        protected ProductService $productService
    ) {}

    public function processCheckout($user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {
            $calculatedSubtotal = 0;
            $orderItemsData = [];
            $now = now();
            $orderId = (string) Str::uuid();

            // Fetch all products at once with locking and eager load variants
            $productIds = collect($data['items'])->pluck('product_id')->unique()->toArray();
            $products = Product::whereIn('id', $productIds)
                ->with('variants')
                ->select(['id', 'name', 'price', 'discount_price', 'image_urls', 'stock_quantity', 'product_sizes'])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Calculate total requested quantity per product to prevent overselling
            $totalQuantityPerProduct = [];
            foreach ($data['items'] as $item) {
                $pid = $item['product_id'];
                $totalQuantityPerProduct[$pid] = ($totalQuantityPerProduct[$pid] ?? 0) + $item['quantity'];
            }

            // Check all items before processing
            foreach ($totalQuantityPerProduct as $productId => $totalQty) {
                if (! isset($products[$productId])) {
                    throw new \Exception("Product not found: {$productId}");
                }

                $product = $products[$productId];
                $productName = is_array($product->name)
                    ? ($product->name['ar'] ?? $product->name['en'] ?? reset($product->name) ?? 'Product')
                    : ($product->name ?? 'Product');

                if ($product->stock_quantity < $totalQty) {
                    throw new \Exception("الكمية المطلوبة غير متوفرة في المخزون للمنتج: {$productName}");
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

                // Deduct stock and increment total sales
                $product->decrement('stock_quantity', $item['quantity']);
                $product->increment('total_sales', $item['quantity']);

                // If product has matching variants, deduct variant stock as well
                if ($product->relationLoaded('variants') && $product->variants->isNotEmpty()) {
                    foreach ($product->variants as $variant) {
                        $attrs = $variant->attributes ?? [];
                        $matchesSize = ! $selectedSize || (isset($attrs['size']) && $attrs['size'] === $selectedSize);
                        $matchesFlavor = ! $selectedFlavor || (isset($attrs['flavor']) && $attrs['flavor'] === $selectedFlavor);

                        if ($matchesSize && $matchesFlavor) {
                            $variant->decrement('stock_quantity', min($variant->stock_quantity, $item['quantity']));
                            break;
                        }
                    }
                }

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

            $order = $this->orderRepository->createOrderWithItems($orderData, $orderItemsData);

            // Invalidate product caches so subsequent API requests get fresh stock immediately
            foreach ($productIds as $pId) {
                $this->productService->clearCaches($pId);
            }

            return $order;
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

    public function updateOrderStatus(string $orderId, string $status, ?string $paymentStatus = null, ?string $trackingNumber = null): \App\Models\Order
    {
        return DB::transaction(function () use ($orderId, $status, $paymentStatus, $trackingNumber) {
            $order = $this->orderRepository->findOrFail($orderId);
            $oldStatus = $order->status;

            // If changing to cancelled from an active order, restore product stock
            if ($status === 'cancelled' && $oldStatus !== 'cancelled') {
                foreach ($order->orderItems as $item) {
                    if ($item->product_id) {
                        Product::where('id', $item->product_id)->increment('stock_quantity', $item->quantity);
                        $this->productService->clearCaches($item->product_id);
                    }
                }
            }

            // If was cancelled and now re-opened, re-deduct stock
            if ($oldStatus === 'cancelled' && $status !== 'cancelled') {
                foreach ($order->orderItems as $item) {
                    if ($item->product_id) {
                        Product::where('id', $item->product_id)->decrement('stock_quantity', $item->quantity);
                        $this->productService->clearCaches($item->product_id);
                    }
                }
            }

            $updatedOrder = $this->orderRepository->updateOrderStatus($order, $status, $paymentStatus, $trackingNumber);

            // Send push notification to customer on status change
            if ($status !== $oldStatus && $updatedOrder->user) {
                $statusMessages = [
                    'processing' => 'تم تأكيد طلبك وهو قيد التجهيز الآن.',
                    'shipped' => 'تم شحن طلبك بنجاح! '.($trackingNumber ? "رقم التتبع: {$trackingNumber}" : ''),
                    'delivered' => 'تم تسليم طلبك بنجاح. شكراً لتسوقك معنا!',
                    'cancelled' => 'تم إلغاء طلبك.',
                ];

                if (isset($statusMessages[$status])) {
                    $this->notificationService->sendToUser(
                        $updatedOrder->user,
                        'تحديث حالة الطلب #'.$order->id,
                        $statusMessages[$status],
                        [
                            'type' => 'order_status_update',
                            'order_id' => (string) $order->id,
                            'status' => $status,
                        ]
                    );
                }
            }

            return $updatedOrder;
        });
    }
}
