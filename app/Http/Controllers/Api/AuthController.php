<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\SafeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'mobile' => 'nullable|string|max:20|unique:users', 'email' => ['required', new SafeEmail, 'unique:users'], 'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()], 'referral_code' => 'nullable|string|exists:users,referral_code']);
        $referrer = isset($data['referral_code']) ? User::where('referral_code', $data['referral_code'])->first() : null;
        unset($data['referral_code']);
        $user = User::create($data + ['status' => 'active', 'referral_code' => Str::upper(Str::random(10))]);
        $user->assignRole(Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']));
        if ($referrer) {
            $referrer->referrals()->create(['referred_user_id' => $user->id]);
        }

        return response()->json(['token' => $user->createToken($request->input('device_name', 'mobile'))->plainTextToken, 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', new SafeEmail], 'password' => 'required|string', 'device_name' => 'required|string|max:100']);
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }
        if (! $user->isActive()) {
            return response()->json(['message' => 'Account suspended.'], 403);
        }
        $user->update(['last_login_at' => now(), 'last_login_ip' => $request->ip()]);

        return ['token' => $user->createToken($data['device_name'])->plainTextToken, 'user' => $user];
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return ['message' => 'Logged out.'];
    }

    public function me(Request $request)
    {
        return $request->user()->load(['devices.snapshot', 'groups', 'subscriptions.plan', 'emergencyContacts']);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate(['name' => 'sometimes|string|max:255', 'mobile' => 'nullable|string|max:20|unique:users,mobile,'.$request->user()->id, 'gender' => 'nullable|in:male,female,other', 'date_of_birth' => 'nullable|date|before:today', 'theme' => 'nullable|in:light,dark', 'profile_image' => 'nullable|image|max:2048']);
        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $request->file('profile_image')->store('users', 'public');
        }
        $request->user()->update($data);

        return $request->user()->fresh();
    }
}
