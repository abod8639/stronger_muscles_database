<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::query()
            ->with('addresses')
            ->withCount('orders')
            ->withSum('orders as total_spent', 'total_amount')
            ->get();

        $activeUsers = $users->where('is_active', true)->count();

        $stats = $users->map(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number,
                'phone_number' => $user->phone_number,
                'role' => $user->role ?? 'user',
                'is_active' => (bool) $user->is_active,
                'photo_url' => $user->photo_url,
                'total_spent' => (float) ($user->total_spent ?? 0),
                'created_at' => $user->created_at->toIso8601String(),
                'last_login' => $user->last_login?->toIso8601String(),
                'addresses' => $user->addresses->map(fn (Address $address) => $this->formatAddress($address))->values(),
                'orders_count' => (int) $user->orders_count,
            ];
        });

        return response()->json([
            'status' => 'success',
            'total_users' => $users->count(),
            'active_users' => $activeUsers,
            'inactive_users' => $users->count() - $activeUsers,
            'users' => $stats,
        ]);
    }

    /**
     * The dashboard's UserAddress model requires a string id and reads camelCase keys;
     * the snake_case keys are kept for existing consumers.
     *
     * @return array<string, mixed>
     */
    protected function formatAddress(Address $address): array
    {
        return [
            'id' => (string) $address->id,
            'user_id' => $address->user_id,
            'label' => $address->label,
            'full_name' => $address->full_name,
            'fullName' => $address->full_name,
            'phone' => $address->phone,
            'street' => $address->street,
            'city' => $address->city,
            'state' => $address->state,
            'postal_code' => $address->postal_code,
            'postalCode' => $address->postal_code,
            'country' => $address->country,
            'is_default' => $address->is_default,
            'isDefault' => $address->is_default,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
