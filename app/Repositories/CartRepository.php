<?php

namespace App\Repositories;

use App\Models\CartItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CartRepository
{
    /**
     * Get all cart items for a user.
     */
    public function getCartForUser(User $user): Collection
    {
        return $user->cartItems()
            ->with(['product:id,name,price,discount_price,stock_quantity,image_urls,brand'])
            ->latest('added_at')
            ->get();
    }

    /**
     * Find a specific cart item for a user.
     */
    public function findItemForUser(User $user, string $cartItemId): CartItem
    {
        return $user->cartItems()->findOrFail($cartItemId);
    }

    /**
     * Find an existing cart item by product ID for a user.
     */
    public function findItemByProductForUser(User $user, string $productId): ?CartItem
    {
        return $user->cartItems()
            ->where('product_id', $productId)
            ->first();
    }

    /**
     * Create a cart item for a user.
     */
    public function createItem(User $user, array $data): CartItem
    {
        return $user->cartItems()->create($data);
    }

    /**
     * Update a cart item.
     */
    public function updateItem(CartItem $cartItem, array $data): CartItem
    {
        $cartItem->update($data);

        return $cartItem;
    }

    /**
     * Delete a cart item.
     */
    public function deleteItem(CartItem $cartItem): bool
    {
        return $cartItem->delete();
    }

    /**
     * Clear all cart items for a user.
     */
    public function clearCartForUser(User $user): int
    {
        return $user->cartItems()->delete();
    }
}
