<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
  /**
   * Handle user profile update.
   */
  public function updateProfile(Request $request)
  {
    $user = $request->user();

    $validator = Validator::make($request->all(), [
      'name' => 'nullable|string',
      'email' => 'nullable|email|unique:users,email,' . $user->id,
      'phone' => 'nullable|string',
      'photo_url' => 'nullable|string',
      'addresses' => 'nullable|array',
      'addresses.*.label' => 'nullable|string',
      'addresses.*.full_name' => 'nullable|string',
      'addresses.*.phone' => 'nullable|string',
      'addresses.*.street' => 'required|string',
      'addresses.*.city' => 'required|string',
      'addresses.*.state' => 'nullable|string',
      'addresses.*.country' => 'nullable|string',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    if ($request->has('name')) {
      $user->name = $request->name;
    }
    if ($request->has('email')) {
      $user->email = $request->email;
    }
    if ($request->has('phone')) {
      $user->phone_number = $request->phone;
    }
    if ($request->has('photo_url')) {
      $user->photo_url = $request->photo_url;
    }

    $user->save();

    // Handle addresses if provided
    if ($request->has('addresses')) {
      // Delete existing addresses
      $user->addresses()->delete();

      // Create new addresses
      foreach ($request->input('addresses') as $addressData) {
        // Map frontend fields to DB columns if necessary, or pass directly if they match
        $user->addresses()->create($addressData);
      }
    }

    return response()->json([
      'status' => 'success',
      'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'phone' => $user->phone_number,
        'photo_url' => $user->photo_url,
        'created_at' => $user->created_at->toIso8601String(),
        'addresses' => $user->addresses,
      ],
    ]);
  }

  /**
   * Handle user login.
   */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photo_url' => $user->photo_url,
                'created_at' => $user->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Handle Google Sign-In.
     */
    public function googleSignIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'required|string',
            'photo_url' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make(Str::random(16)), // Dummy password for Google users
                'photo_url' => $request->photo_url,
                'email_verified_at' => now(),
            ]);
        } else {
            // Update photo if changed/provided
            if ($request->has('photo_url') && $user->photo_url !== $request->photo_url) {
                $user->update(['photo_url' => $request->photo_url]);
            }
        }

        // Generate real Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photo_url' => $user->photo_url,
                'token' => $token,
                'created_at' => $user->created_at->toIso8601String(),
            ],
        ]);
    }
}
