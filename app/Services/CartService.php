<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Repositories\CartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(
        protected CartRepository $cartRepository
    ) {}

    /**
     * Get user cart with calculated totals.
     */
    public function getUserCart(User $user): array
    {
        $cartItems = $this->cartRepository->getCartForUser($user)
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'user_id' => (string) $item->user_id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'price' => (float) $item->price,
                    'image_urls' => $item->image_urls,
                    'quantity' => (int) $item->quantity,
                    'flavors' => $item->flavors,
                    'size' => $item->size,
                    'selected_flavor' => is_array($item->flavors) ? ($item->flavors[0] ?? null) : $item->flavors,
                    'selected_size' => is_array($item->size) ? ($item->size[0] ?? null) : $item->size,
                    'flavor' => is_array($item->flavors) ? ($item->flavors[0] ?? null) : $item->flavors,
                    'added_at' => $item->added_at,
                    'total_price' => (float) $item->getTotalPriceAttribute(),
                    'product' => $item->product ? (new \App\Http\Resources\Api\V1\ProductResource($item->product))->resolve() : null,
                ];
            });

        return [
            'items' => $cartItems,
            'grand_total' => (float) $cartItems->sum('total_price'),
        ];
    }

    /**
     * Add item to cart with stock validation and DB price calculation.
     */
    public function addToCart(User $user, array $data): CartItem
    {
        return DB::transaction(function () use ($user, $data) {
            $product = Product::active()
                ->inStock()
                ->with(['variants'])
                ->select(['id', 'name', 'price', 'discount_price', 'image_urls', 'stock_quantity'])
                ->findOrFail($data['product_id']);

            $flavor = $data['selected_flavor'] ?? $data['flavor'] ?? null;
            $size = $data['selected_size'] ?? $data['size'] ?? null;
            $variantId = $data['variant_id'] ?? null;

            // Match variant if variant_id provided or if matching size/flavor attributes exist
            $variant = null;
            if ($variantId) {
                $variant = $product->variants->firstWhere('id', $variantId);
            } elseif ($flavor || $size) {
                $variant = $product->variants->first(function ($v) use ($flavor, $size) {
                    $attrs = $v->attributes ?? [];
                    $matchSize = ! $size || (isset($attrs['size']) && $attrs['size'] === $size);
                    $matchFlavor = ! $flavor || (isset($attrs['flavor']) && $attrs['flavor'] === $flavor);

                    return $matchSize && $matchFlavor;
                });
            }

            $effectiveVariantId = $variant?->id ?? $variantId;
            $availableStock = $variant ? $variant->stock_quantity : $product->stock_quantity;

            if ($data['quantity'] > $availableStock) {
                throw ValidationException::withMessages([
                    'quantity' => ['الكمية المطلوبة تتجاوز المخزون المتوفر'],
                ]);
            }

            $price = ($product->discount_price > 0 && $product->discount_price < $product->price)
                ? $product->discount_price
                : $product->price;

            if ($variant && $variant->price > 0) {
                $price = ($variant->discount_price > 0 && $variant->discount_price < $variant->price)
                    ? $variant->discount_price
                    : $variant->price;
            }

            $existingItem = $this->cartRepository->findItemByProductAndAttributesForUser(
                $user,
                $data['product_id'],
                $flavor,
                $size,
                $effectiveVariantId
            );

            if ($existingItem) {
                $newQuantity = $existingItem->quantity + $data['quantity'];

                if ($newQuantity > $availableStock) {
                    throw ValidationException::withMessages([
                        'quantity' => ['إجمالي الكمية المطلوبة يتجاوز المخزون المتوفر'],
                    ]);
                }

                return $this->cartRepository->updateItem($existingItem, [
                    'quantity' => $newQuantity,
                    'variant_id' => $effectiveVariantId,
                    'flavors' => $flavor ? [$flavor] : ($existingItem->flavors ?? []),
                    'size' => $size ? (is_array($size) ? $size : [$size]) : ($existingItem->size ?? []),
                    'added_at' => now(),
                ]);
            }

            $productName = is_array($product->name)
                ? ($product->name['ar'] ?? $product->name['en'] ?? json_encode($product->name, JSON_UNESCAPED_UNICODE))
                : (string) $product->name;

            return $this->cartRepository->createItem($user, [
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'product_id' => $data['product_id'],
                'variant_id' => $effectiveVariantId,
                'product_name' => $productName,
                'price' => $price,
                'image_urls' => $product->image_urls,
                'quantity' => $data['quantity'],
                'flavors' => $flavor ? [$flavor] : ($data['flavors'] ?? []),
                'size' => $size ? (is_array($size) ? $size : [$size]) : ($data['size'] ?? []),
                'added_at' => now(),
            ]);
        });
    }

    /**
     * Update quantity of an item in cart.
     */
    public function updateCartItemQuantity(User $user, string $cartItemId, int $quantity): CartItem
    {
        $cartItem = $this->cartRepository->findItemForUser($user, $cartItemId);

        $product = $cartItem->product;
        if ($quantity > $product->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['Requested quantity exceeds available stock'],
            ]);
        }

        return $this->cartRepository->updateItem($cartItem, [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Remove item from cart.
     */
    public function removeFromCart(User $user, string $cartItemId): bool
    {
        $cartItem = $this->cartRepository->findItemForUser($user, $cartItemId);

        return $this->cartRepository->deleteItem($cartItem);
    }

    /**
     * Clear all items in cart.
     */
    public function clearCart(User $user): int
    {
        return $this->cartRepository->clearCartForUser($user);
    }
}
