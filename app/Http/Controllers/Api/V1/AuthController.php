<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleSignInRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateFcmTokenRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Get user profile.
     */
    public function testLogin()
    {
        $user = User::first();
        if (! $user) {
            $user = User::factory()->create(['email' => 'admin@test.com', 'role' => 'admin']);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Get user profile.
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();

        // Eager load everything needed for the resource to avoid N+1 and slow accessors
        $user = User::query()
            ->with('addresses')
            ->withCount('orders')
            ->withSum(['orders as total_spent' => function ($query) {
                $query->where('status', 'delivered');
            }], 'total_amount')
            ->findOrFail($user->id);

        return response()->json([
            'status' => 'success',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Handle user profile update.
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();

        $validated = $request->validated();

        // Only update provided fields
        $updateData = [];
        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (isset($validated['email'])) {
            $updateData['email'] = $validated['email'];
        }
        if (isset($validated['phone'])) {
            $updateData['phone_number'] = $validated['phone'];
        }
        if (isset($validated['photo_url'])) {
            $updateData['photo_url'] = $validated['photo_url'];
        }
        if (isset($validated['preferred_language'])) {
            $updateData['preferred_language'] = $validated['preferred_language'];
        }
        if (isset($validated['notifications_enabled'])) {
            $updateData['notifications_enabled'] = $validated['notifications_enabled'];
        }

        if (! empty($updateData)) {
            $user->update($updateData);
        }

        if ($request->has('addresses') && is_array($request->input('addresses'))) {
            $user->addresses()->delete();
            foreach ($request->input('addresses') as $addrData) {
                $user->addresses()->create([
                    'label' => $addrData['label'] ?? 'Home',
                    'full_name' => $addrData['full_name'] ?? $user->name,
                    'phone' => $addrData['phone'] ?? $user->phone_number,
                    'street' => $addrData['street'] ?? '',
                    'city' => $addrData['city'] ?? '',
                    'state' => $addrData['state'] ?? null,
                    'country' => $addrData['country'] ?? null,
                    'postal_code' => $addrData['postal_code'] ?? null,
                    'is_default' => $addrData['is_default'] ?? false,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'user' => new UserResource($user->fresh()->load('addresses')),
        ]);
    }

    /**
     * Handle user login.
     */
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $request->email)->first();

        // Check if user exists
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials',
                'error_type' => 'email_not_found',
            ], 401);
        }

        // Check if password is correct
        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials',
                'error_type' => 'invalid_password',
            ], 401);
        }

        // Update last login timestamp
        $user->update(['last_login' => now()]);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => new UserResource($user->load('addresses')),
        ]);
    }

    /**
     * Handle user registration.
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'], // Rely on 'hashed' cast in User model
            'role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(), // Auto-verify for now
        ]);

        // Update last login
        $user->update(['last_login' => now()]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'تم إنشاء الحساب بنجاح',
            'token' => $token,
            'user' => new UserResource($user->load('addresses')),
        ], 200);
    }

    /**
     * Handle Google Sign-In.
     */
    public function googleSignIn(GoogleSignInRequest $request)
    {
        $validated = $request->validated();

        $user = User::firstOrCreate(
            ['email' => $request->email],
            [
                'name' => $request->name,
                'password' => Str::random(16), // Rely on 'hashed' cast
                'photo_url' => $request->photo_url,
                'email_verified_at' => now(),
                'role' => 'user', // Default role
                'is_active' => true,
            ]
        );

        // Update photo if changed and provided
        if ($request->has('photo_url') && $user->photo_url !== $request->photo_url) {
            $user->update(['photo_url' => $request->photo_url]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->load('addresses')),
        ]);
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Update user FCM device token.
     */
    public function updateFcmToken(UpdateFcmTokenRequest $request)
    {
        $request->user()->update([
            'fcm_token' => $request->validated('fcm_token'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'FCM token updated successfully',
        ]);
    }
}
