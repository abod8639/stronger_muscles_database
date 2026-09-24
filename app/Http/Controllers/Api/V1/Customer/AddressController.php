<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Address\StoreAddressRequest;
use App\Http\Requests\Customer\Address\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Services\AddressService;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(
        protected AddressService $addressService
    ) {}

    /**
     * Display a listing of the user's addresses.
     */
    public function index(Request $request)
    {
        $addresses = $this->addressService->getUserAddresses($request->user());

        return response()->json([
            'status' => 'success',
            'addresses' => AddressResource::collection($addresses),
        ]);
    }

    /**
     * Store a newly created address.
     */
    public function store(StoreAddressRequest $request)
    {
        $validated = $request->validated();

        $address = $this->addressService->createAddress($request->user(), $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Address created successfully',
            'address' => new AddressResource($address),
        ], 201);
    }

    /**
     * Display the specified address.
     */
    public function show(Request $request, int $id)
    {
        $address = $this->addressService->getAddress($request->user(), $id);

        return response()->json([
            'status' => 'success',
            'address' => new AddressResource($address),
        ]);
    }

    /**
     * Update the specified address.
     */
    public function update(UpdateAddressRequest $request, int $id)
    {
        $validated = $request->validated();

        $address = $this->addressService->updateAddress($request->user(), $id, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Address updated successfully',
            'address' => new AddressResource($address),
        ]);
    }

    /**
     * Remove the specified address.
     */
    public function destroy(Request $request, int $id)
    {
        $this->addressService->deleteAddress($request->user(), $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Address deleted successfully',
        ]);
    }

    /**
     * Set an address as the default.
     */
    public function setDefault(Request $request, int $id)
    {
        $address = $this->addressService->setDefaultAddress($request->user(), $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Default address updated successfully',
            'address' => new AddressResource($address),
        ]);
    }
}
