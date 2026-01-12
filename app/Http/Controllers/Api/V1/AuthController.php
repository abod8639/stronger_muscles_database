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

    if (!$user) {
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

    // Sanctum is not installed, so we cannot generate a token yet.
    // $token = $user->createToken('auth_token')->plainTextToken;
    $token = 'sanctum-not-installed-placeholder-token';

    return response()->json([
      'status' => 'success',
      'access_token' => $token,
      'token_type' => 'Bearer',
      'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'photo_url' => $user->photo_url,
        'token' => $token, // إرسال التوكن داخل كائن المستخدم يسهل العمل في Flutter
        'role' => $user->role ?? 'user',
        'created_at' => $user->created_at->toIso8601String(),
      ],
    ]);
  }
}
