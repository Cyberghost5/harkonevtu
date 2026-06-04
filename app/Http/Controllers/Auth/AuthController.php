<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Mail\LoginOtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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

            $user = Auth::user();

            // If OTP verification is enabled, hold off on full session auth
            if (AppSetting::get('otp_verification', '0') === '1') {
                Auth::logout();
                $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                Cache::put('login_otp_' . $user->id, $otp, now()->addMinutes(10));
                session([
                    'login_otp_uid'      => $user->id,
                    'login_otp_remember' => $request->boolean('remember'),
                ]);
                $this->sendLoginOtpEmail($user, $otp);
                return redirect()->route('login.otp');
            }

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
        // Persist referral code from URL into session so it survives the POST
        if (request()->filled('ref')) {
            session(['ref_code' => strtoupper(trim(request('ref')))]);
        }

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

        // Redirect based on email verification setting
        if (AppSetting::get('email_verification', '1') === '1') {
            return redirect()->route('verification.notice');
        }

        // Verification disabled - go directly to PIN setup
        return redirect()->route('pin.setup');
    }

    // ─── Login OTP ────────────────────────────────────────────────────────────

    public function showLoginOtp(): View|RedirectResponse
    {
        $uid = session('login_otp_uid');
        if (!$uid || !User::find($uid)) {
            session()->forget(['login_otp_uid', 'login_otp_remember']);
            return redirect()->route('login');
        }

        $email = User::find($uid)->email;
        [$local, $domain] = explode('@', $email, 2);
        $maskedEmail = substr($local, 0, 2) . str_repeat('*', max(strlen($local) - 2, 3)) . '@' . $domain;

        return view('auth.verify-login-otp', ['maskedEmail' => $maskedEmail]);
    }

    public function verifyLoginOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $uid = session('login_otp_uid');
        if (!$uid) {
            return redirect()->route('login')->with('error', 'Session expired. Please login again.');
        }

        $stored = Cache::get('login_otp_' . $uid);

        if (!$stored || $stored !== $request->input('otp')) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP. Please try again.']);
        }

        $user = User::find($uid);
        if (!$user) {
            return redirect()->route('login');
        }

        Cache::forget('login_otp_' . $uid);
        $remember = session('login_otp_remember', false);
        session()->forget(['login_otp_uid', 'login_otp_remember']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function resendLoginOtp(Request $request): RedirectResponse
    {
        $uid = session('login_otp_uid');
        if (!$uid) {
            return redirect()->route('login');
        }

        $rateLimitKey = 'login_otp_rate_' . $uid;
        if (Cache::has($rateLimitKey)) {
            return back()->with('error', 'Please wait 60 seconds before requesting another OTP.');
        }

        $user = User::find($uid);
        if (!$user) {
            return redirect()->route('login');
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put('login_otp_' . $uid, $otp, now()->addMinutes(10));
        Cache::put($rateLimitKey, true, 60);

        $this->sendLoginOtpEmail($user, $otp);

        return back()->with('success', 'A new OTP has been sent to your email address.');
    }

    private function sendLoginOtpEmail(User $user, string $otp): void
    {
        config([
            'mail.mailers.smtp.host'       => AppSetting::get('mail_host', config('mail.mailers.smtp.host')),
            'mail.mailers.smtp.port'       => AppSetting::get('mail_port', config('mail.mailers.smtp.port')),
            'mail.mailers.smtp.username'   => AppSetting::get('mail_username', config('mail.mailers.smtp.username')),
            'mail.mailers.smtp.password'   => AppSetting::get('mail_password', config('mail.mailers.smtp.password')),
            'mail.from.address'            => AppSetting::get('mail_from_address', config('mail.from.address')),
            'mail.from.name'               => AppSetting::get('site_name', config('mail.from.name')),
        ]);

        $siteName = AppSetting::get('site_name', config('app.name'));

        try {
            Mail::to($user->email)->send(new LoginOtpMail($user, $otp));
        } catch (\Exception $e) {
            Log::warning('[Login OTP] Failed to send email to ' . $user->email . ': ' . $e->getMessage());
        }

        Log::info('[Login OTP] User: ' . $user->email . ' | OTP: ' . $otp);
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
