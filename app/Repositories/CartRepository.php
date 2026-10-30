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
            ->with(['product'])
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
     * Find an existing cart item by product ID and attributes for a user.
     */
    public function findItemByProductAndAttributesForUser(
        User $user,
        string $productId,
        ?string $flavor = null,
        ?string $size = null,
        ?string $variantId = null
    ): ?CartItem {
        return $user->cartItems()
            ->where('product_id', $productId)
            ->get()
            ->first(function (CartItem $item) use ($flavor, $size, $variantId) {
                if ($variantId && $item->variant_id === $variantId) {
                    return true;
                }
                $itemFlavor = is_array($item->flavors) ? ($item->flavors[0] ?? null) : $item->flavors;
                $itemSize = is_array($item->size) ? ($item->size[0] ?? null) : $item->size;

                return $itemFlavor === $flavor && $itemSize === $size;
            });
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
