<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get user statistics and order details.
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
        'addresses' => [], // Placeholder as no addresses table exists
        'orders_count' => (int) $user->orders_count,
            ];
        });

        return response()->json([
            'total_users' => $users->count(),
            'users' => $stats,
        ]);
    }
}
