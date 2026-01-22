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
      'phone' => 'nullable|string',
      'photo_url' => 'nullable|string',
      'addresses' => 'nullable|array',
      'addresses.*.id' => 'nullable|string',
      'addresses.*.label' => 'nullable|string',
      'addresses.*.full_name' => 'nullable|string',
      'addresses.*.phone' => 'nullable|string',
      'addresses.*.street' => 'nullable|string',
      'addresses.*.city' => 'nullable|string',
      'addresses.*.state' => 'nullable|string',
      'addresses.*.country' => 'nullable|string',
    ]);

    return DB::transaction(function () use ($user, $request) {
      $user->fill(array_filter([
        'name' => $request->name,
        'email' => $request->email,
        'phone_number' => $request->phone,
        'photo_url' => $request->photo_url,
      ]));

      if ($user->isDirty()) {
        $user->save();
      }

      if ($request->has('addresses')) {
        foreach ($request->addresses as $addressData) {
          $user->addresses()->updateOrCreate(
            ['id' => $addressData['id'] ?? (string) Str::uuid()],
            $addressData
          );
        }
      }

      return response()->json([
        'status' => 'success',
        'message' => 'Profile updated successfully',
        'user' => new UserResource($user->load('addresses')),
      ]);
    });
  }

  /**
   * Handle user login.
   */
    public function login(Request $request)
    {
    $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
      'user' => new UserResource($user),
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
      'name' => $request->name,
      'email' => $request->email,
      'password' => Hash::make($request->password),
      'role' => 'user',
      'is_active' => true,
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
      'status' => 'success',
      'message' => 'User registered successfully',
      'token' => $token,
      'user' => new UserResource($user),
    ]);
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
