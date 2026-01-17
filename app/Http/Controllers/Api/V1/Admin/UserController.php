<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::withCount('orders')
            ->withSum('orders as total_spent', 'total_amount')
            ->get();

        $stats = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number,
                'is_active' => (bool) $user->is_active,
                'photo_url' => $user->photo_url,
                'total_spent' => (float) ($user->total_spent ?? 0),
                'created_at' => $user->created_at->toIso8601String(),
                'last_login' => null, // Placeholder as not currently tracked in users table
                'addresses' => $user->addresses, // Updated to include actual addresses
                'orders_count' => (int) $user->orders_count,
            ];
        });

        return response()->json([
            'status' => 'success',
            'total_users' => $users->count(),
            'users' => $stats,
        ]);
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
