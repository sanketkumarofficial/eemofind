<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password'])]);

        return response()->json(['token' => $user->createToken('mobile')->plainTextToken, 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);
        $user = User::where('email', $data['email'])->first();

        abort_if(! $user || ! Hash::check($data['password'], $user->password), 422, 'Invalid credentials.');

        return response()->json(['token' => $user->createToken('mobile')->plainTextToken, 'user' => $user]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}