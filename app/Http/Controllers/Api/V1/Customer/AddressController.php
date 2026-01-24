<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Http\Resources\AddressResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    /**
     * Display a listing of the user's addresses.
     */
    public function index(Request $request)
    {
        $addresses = $request->user()
            ->addresses()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'addresses' => AddressResource::collection($addresses),
        ]);
    }

    /**
     * Store a newly created address.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'full_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'is_default' => 'nullable|boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $user = $request->user();

            // If this is set as default, unset all other defaults
            if ($validated['is_default'] ?? false) {
                $user->addresses()->update(['is_default' => false]);
            }

            // Create the address
            $address = $user->addresses()->create($validated);

            // Update user's default_address_id if this is the default
            if ($address->is_default) {
                $user->update(['default_address_id' => (string)$address->id]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Address created successfully',
                'address' => new AddressResource($address),
            ], 201);
        });
    }

    /**
     * Display the specified address.
     */
    public function show(Request $request, int $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'address' => new AddressResource($address),
        ]);
    }

    /**
     * Update the specified address.
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'full_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'is_default' => 'nullable|boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        return DB::transaction(function () use ($request, $id, $validated) {
            $user = $request->user();
            $address = $user->addresses()->findOrFail($id);

            // If setting as default, unset all other defaults
            if (isset($validated['is_default']) && $validated['is_default']) {
                $user->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
            }

            $address->update($validated);

            // Update user's default_address_id if this is the default
            if ($address->is_default) {
                $user->update(['default_address_id' => (string)$address->id]);
            } elseif ($user->default_address_id == $id && !$address->is_default) {
                // If this was the default and is being unset, clear user's default
                $user->update(['default_address_id' => null]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Address updated successfully',
                'address' => new AddressResource($address->fresh()),
            ]);
        });
    }

    /**
     * Remove the specified address.
     */
    public function destroy(Request $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $user = $request->user();
            $address = $user->addresses()->findOrFail($id);

            // If deleting the default address, clear user's default_address_id
            if ($user->default_address_id == $id) {
                $user->update(['default_address_id' => null]);
            }

            $address->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Address deleted successfully',
            ]);
        });
    }

    /**
     * Set an address as the default.
     */
    public function setDefault(Request $request, int $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $user = $request->user();
            $address = $user->addresses()->findOrFail($id);

            // Unset all other defaults
            $user->addresses()->update(['is_default' => false]);

            // Set this one as default
            $address->update(['is_default' => true]);

            // Update user's default_address_id
            $user->update(['default_address_id' => (string)$address->id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Default address updated successfully',
                'address' => new AddressResource($address->fresh()),
            ]);
        });
    }
}
