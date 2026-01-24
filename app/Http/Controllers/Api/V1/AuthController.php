<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
  /**
   * Get user profile.
   */
  public function testLogin()
  {
    $user = User::first();
    if (!$user) {
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

    return response()->json([
      'status' => 'success',
      'user' => new UserResource($user->load('addresses')),
    ]);
  }

  /**
   * Handle user profile update.
   */
  public function updateProfile(Request $request)
  {
    $user = $request->user();

    $validated = $request->validate([
      'name' => 'nullable|string|max:255',
      'email' => 'nullable|email|unique:users,email,' . $user->id,
      'phone' => 'nullable|string|max:20',
      'photo_url' => 'nullable|string|max:500',
      'preferred_language' => 'nullable|string|in:ar,en',
      'notifications_enabled' => 'nullable|boolean',
    ]);

    // Only update provided fields
    $updateData = [];
    if (isset($validated['name']))
      $updateData['name'] = $validated['name'];
    if (isset($validated['email']))
      $updateData['email'] = $validated['email'];
    if (isset($validated['phone']))
      $updateData['phone_number'] = $validated['phone'];
    if (isset($validated['photo_url']))
      $updateData['photo_url'] = $validated['photo_url'];
    if (isset($validated['preferred_language']))
      $updateData['preferred_language'] = $validated['preferred_language'];
    if (isset($validated['notifications_enabled']))
      $updateData['notifications_enabled'] = $validated['notifications_enabled'];

    if (!empty($updateData)) {
      $user->update($updateData);
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
    public function login(Request $request)
    {
    $validated = $request->validate([
            'email' => 'required|email',
      'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
      return response()->json([
        'status' => 'error',
        'message' => 'Invalid credentials'
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
  public function register(Request $request)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'email' => 'required|string|email|max:255|unique:users',
      'password' => 'required|string|min:6',
    ]);

    $user = User::create([
      'name' => $validated['name'],
      'email' => $validated['email'],
      'password' => Hash::make($validated['password']),
      'role' => 'user',
      'is_active' => true,
      'email_verified_at' => now(), // Auto-verify for now
    ]);

    // Update last login
    $user->update(['last_login' => now()]);

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
      'status' => 'success',
      'message' => 'User registered successfully',
      'token' => $token,
      'user' => new UserResource($user->load('addresses')),
    ], 201);
  }

  /**
   * Handle Google Sign-In.
   */
    public function googleSignIn(Request $request)
    {
    $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
            'photo_url' => 'nullable|string',
      'google_id' => 'nullable|string', // Optional if you want to store/validate it later
        ]);

    $user = User::firstOrCreate(
      ['email' => $request->email],
      [
                'name' => $request->name,
        'password' => Hash::make(Str::random(16)), // Dummy password
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
      'user' => new UserResource($user),
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
}
