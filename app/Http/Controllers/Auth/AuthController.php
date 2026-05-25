<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ─── Login ────────────────────────────────────────────────────────────────

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Rate limiting: max 5 attempts per email+IP per minute
        $key = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::clear($key);
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($key);

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    // ─── Register ─────────────────────────────────────────────────────────────

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone'    => ['required', 'string', 'regex:/^0[789]\d{9}$/', 'unique:users'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
            'terms'    => ['accepted'],
        ], [
            'phone.regex'        => 'Please enter a valid Nigerian phone number (e.g. 08012345678).',
            'terms.accepted'     => 'You must accept the terms and conditions to continue.',
            'password.confirmed' => 'The passwords you entered do not match.',
        ]);

        // Generate unique username (firstname + random digits)
        $base     = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $validated['name'])[0]));
        $username = $base . rand(10, 9999);
        while (User::where('username', $username)->exists()) {
            $username = $base . rand(10, 9999);
        }

        // Generate unique referral code
        $referralCode = strtoupper(Str::random(8));
        while (User::where('referral_code', $referralCode)->exists()) {
            $referralCode = strtoupper(Str::random(8));
        }

        $user = User::create([
            'name'          => $validated['name'],
            'email'         => $validated['email'],
            'phone'         => $validated['phone'],
            'password'      => Hash::make($validated['password']),
            'username'      => $username,
            'user_type'     => 'normal',
            'referral_code' => $referralCode,
            'referred_by'   => session('ref_code'), // optional referral tracking
        ]);

        // Auto-create wallet
        $user->wallet()->create([
            'balance'      => 0.00,
            'total_funded' => 0.00,
            'total_spent'  => 0.00,
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Redirect based on verification requirement
        $verification = AppSetting::get('require_verification', 'none');

        if ($verification === 'email') {
            return redirect()->route('verification.notice');
        }

        if ($verification === 'phone') {
            // OTP auto-dispatched by VerificationController::showPhoneVerification() on first load
            return redirect()->route('verification.phone');
        }

        // No verification required - go directly to PIN setup
        return redirect()->route('pin.setup');
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    // ─── Forgot Password ──────────────────────────────────────────────────────

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::ResetLinkSent
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    // ─── Reset Password ───────────────────────────────────────────────────────

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
