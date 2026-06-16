<?php

namespace App\Http\Controllers;

use App\Rules\SafeEmail;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request, ActivityService $activity)
    {
        $credentials = $request->validate(['email' => ['required', new SafeEmail], 'password' => ['required', 'string']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }
        if (! $request->user()->isActive()) {
            Auth::logout();

            return back()->withErrors(['email' => 'Your account is suspended.']);
        }
        $request->session()->regenerate();
        $request->session()->put('authenticated_at', time());
        $request->user()->update(['last_login_at' => now(), 'last_login_ip' => $request->ip()]);
        $activity->log('authentication', 'login', 'Signed in to Eemo Find.');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request, ActivityService $activity)
    {
        $activity->log('authentication', 'logout', 'Signed out of Eemo Find.');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => ['required', new SafeEmail]]);

        return back()->with('status', Password::sendResetLink($request->only('email')));
    }

    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate(['token' => 'required', 'email' => ['required', new SafeEmail], 'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()]]);
        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
        });

        return $status === Password::PASSWORD_RESET ? redirect()->route('login')->with('status', __($status)) : back()->withErrors(['email' => __($status)]);
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()]]);
        $request->user()->update(['password' => $data['password']]);
        $request->user()->tokens()->delete();

        return back()->with('success', 'Password changed successfully.');
    }
}
