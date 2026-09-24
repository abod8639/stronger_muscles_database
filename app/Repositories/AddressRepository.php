<?php

namespace App\Repositories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AddressRepository
{
    /**
     * Get all addresses for a user.
     */
    public function getUserAddresses(User $user): Collection
    {
        return $user->addresses()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find a user address by ID.
     */
    public function findUserAddress(User $user, int $id): Address
    {
        return $user->addresses()->findOrFail($id);
    }

    /**
     * Create an address for a user.
     */
    public function createAddress(User $user, array $data): Address
    {
        return $user->addresses()->create($data);
    }

    /**
     * Update an address.
     */
    public function updateAddress(Address $address, array $data): Address
    {
        $address->update($data);

        return $address;
    }

    /**
     * Delete an address.
     */
    public function deleteAddress(Address $address): bool
    {
        return $address->delete();
    }

    /**
     * Unset default status on user's addresses.
     */
    public function unsetDefaultAddresses(User $user, ?int $exceptId = null): void
    {
        $query = $user->addresses();

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['is_default' => false]);
    }
}
