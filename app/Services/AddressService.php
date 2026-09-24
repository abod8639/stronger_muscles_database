<?php

namespace App\Services;

use App\Models\Address;
use App\Models\User;
use App\Repositories\AddressRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AddressService
{
    public function __construct(
        protected AddressRepository $addressRepository
    ) {}

    /**
     * Get user addresses.
     */
    public function getUserAddresses(User $user): Collection
    {
        return $this->addressRepository->getUserAddresses($user);
    }

    /**
     * Get single address for user.
     */
    public function getAddress(User $user, int $id): Address
    {
        return $this->addressRepository->findUserAddress($user, $id);
    }

    /**
     * Create address and update defaults atomically.
     */
    public function createAddress(User $user, array $data): Address
    {
        return DB::transaction(function () use ($user, $data) {
            if ($data['is_default'] ?? false) {
                $this->addressRepository->unsetDefaultAddresses($user);
            }

            $address = $this->addressRepository->createAddress($user, $data);

            if ($address->is_default) {
                $user->update(['default_address_id' => (string) $address->id]);
            }

            return $address;
        });
    }

    /**
     * Update address and handle default flags atomically.
     */
    public function updateAddress(User $user, int $id, array $data): Address
    {
        return DB::transaction(function () use ($user, $id, $data) {
            $address = $this->addressRepository->findUserAddress($user, $id);

            if (isset($data['is_default']) && $data['is_default']) {
                $this->addressRepository->unsetDefaultAddresses($user, $id);
            }

            $this->addressRepository->updateAddress($address, $data);

            if ($address->is_default) {
                $user->update(['default_address_id' => (string) $address->id]);
            } elseif ($user->default_address_id == $id && ! $address->is_default) {
                $user->update(['default_address_id' => null]);
            }

            return $address->fresh();
        });
    }

    /**
     * Delete address and handle user's default address reference.
     */
    public function deleteAddress(User $user, int $id): bool
    {
        return DB::transaction(function () use ($user, $id) {
            $address = $this->addressRepository->findUserAddress($user, $id);

            if ($user->default_address_id == $id) {
                $user->update(['default_address_id' => null]);
            }

            return $this->addressRepository->deleteAddress($address);
        });
    }

    /**
     * Explicitly set an address as the default.
     */
    public function setDefaultAddress(User $user, int $id): Address
    {
        return DB::transaction(function () use ($user, $id) {
            $address = $this->addressRepository->findUserAddress($user, $id);

            $this->addressRepository->unsetDefaultAddresses($user, $id);
            $this->addressRepository->updateAddress($address, ['is_default' => true]);

            $user->update(['default_address_id' => (string) $address->id]);

            return $address->fresh();
        });
    }
}
