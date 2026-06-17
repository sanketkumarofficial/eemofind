<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Models\EmailOtp;
use Illuminate\Support\Facades\Mail;

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

    public function sendOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    $user = User::where('email', $request->email)
        ->where('status', 'active')
        ->first();

    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

    $otp = rand(100000,999999);

    EmailOtp::updateOrCreate(
        ['email' => $user->email],
        [
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5),
            'verified_at' => null
        ]
    );

    Mail::raw(
        "Your EemoFind OTP is {$otp}. Valid for 5 minutes.",
        function ($message) use ($user) {
            $message->to($user->email)
                ->subject('EemoFind Login OTP');
        }
    );

    return response()->json([
        'success' => true,
        'message' => 'OTP sent successfully'
    ]);
}

public function verifyOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required|digits:6'
    ]);

    $otpRecord = EmailOtp::where('email', $request->email)
        ->where('otp', $request->otp)
        ->first();

    if (!$otpRecord) {
        return response()->json([
            'message' => 'Invalid OTP'
        ], 422);
    }

    if ($otpRecord->expires_at->lt(now())) {

        $otpRecord->delete();

        return response()->json([
            'message' => 'OTP expired'
        ], 422);
    }

    $user = User::where('email', $request->email)->first();

    $user->update([
        'last_login_at' => now(),
        'last_login_ip' => request()->ip()
    ]);

    $token = $user->createToken('mobile')->plainTextToken;

    $otpRecord->delete();

    return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => $user
    ]);
}

}