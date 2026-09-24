<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Repositories\CartRepository;
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
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'price' => (float) $item->price,
                    'image_urls' => $item->image_urls,
                    'quantity' => (int) $item->quantity,
                    'flavors' => $item->flavors,
                    'size' => $item->size,
                    'added_at' => $item->added_at,
                    'total_price' => (float) $item->getTotalPriceAttribute(),
                    'product' => $item->product,
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
        $product = Product::active()
            ->inStock()
            ->select(['id', 'name', 'price', 'discount_price', 'image_urls', 'stock_quantity'])
            ->findOrFail($data['product_id']);

        if ($data['quantity'] > $product->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['Requested quantity exceeds available stock'],
            ]);
        }

        $price = ($product->discount_price > 0 && $product->discount_price < $product->price)
            ? $product->discount_price
            : $product->price;

        $existingItem = $this->cartRepository->findItemByProductForUser($user, $data['product_id']);

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $data['quantity'];

            if ($newQuantity > $product->stock_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Total quantity exceeds available stock'],
                ]);
            }

            return $this->cartRepository->updateItem($existingItem, [
                'quantity' => $newQuantity,
                'added_at' => now(),
            ]);
        }

        return $this->cartRepository->createItem($user, [
            'id' => (string) Str::uuid(),
            'product_id' => $data['product_id'],
            'product_name' => $product->name,
            'price' => $price,
            'image_urls' => $product->image_urls,
            'quantity' => $data['quantity'],
            'flavors' => $data['flavors'] ?? [],
            'size' => $data['size'] ?? [],
            'added_at' => now(),
        ]);
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
