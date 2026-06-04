<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Mail\PinResetMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class UserSettingsController extends Controller
{
    // ─── Show settings page ───────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $user      = $request->user();
        $wallet    = $user->wallet;
        $referrals = \App\Models\User::where('referred_by', $user->referral_code)->count();
        $tab       = $request->query('tab', 'profile');

        $siteName  = AppSetting::get('site_name', config('app.name'));

        return view('settings.index', compact('user', 'wallet', 'referrals', 'tab', 'siteName'));
    }

    // ─── Profile (name + username + avatar) ──────────────────────────────────

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'username' => ['required', 'alpha_dash', 'max:30', "unique:users,username,{$user->id}"],
            'name'     => ['required', 'string', 'max:100'],
            'avatar'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->username = $request->username;
        $user->name     = $request->name;
        $user->save();

        return redirect()->route('settings', ['tab' => 'profile'])
            ->with('success', 'Profile updated successfully.');
    }

    // ─── Low balance notification toggle ─────────────────────────────────────

    public function updateNotification(Request $request): RedirectResponse
    {
        $request->validate(['low_balance_notification' => ['nullable', 'boolean']]);

        $request->user()->update([
            'low_balance_notification' => $request->boolean('low_balance_notification'),
        ]);

        return redirect()->route('settings', ['tab' => 'profile'])
            ->with('success', 'Notification preference saved.');
    }

    // ─── Change Password ──────────────────────────────────────────────────────

    public function changePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'string', function ($attr, $val, $fail) use ($user) {
                if (!Hash::check($val, $user->password)) {
                    $fail('The current password is incorrect.');
                }
            }],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('settings', ['tab' => 'account'])
            ->with('success', 'Password changed successfully.');
    }

    // ─── Change PIN ───────────────────────────────────────────────────────────

    public function changePin(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'old_pin'     => ['required', 'digits:4'],
            'new_pin'     => ['required', 'digits:4', 'different:old_pin'],
            'confirm_pin' => ['required', 'same:new_pin'],
        ], [
            'new_pin.different'  => 'New PIN must be different from your old PIN.',
            'confirm_pin.same'   => 'PIN confirmation does not match.',
        ]);

        if (!$user->verifyPin($request->old_pin)) {
            return back()->withErrors(['old_pin' => 'Incorrect current PIN.'])
                ->with('active_form', 'change_pin');
        }

        $user->transaction_pin = Hash::make($request->new_pin);
        $user->save();

        return redirect()->route('settings', ['tab' => 'transactions'])
            ->with('success', 'Transaction PIN changed successfully.');
    }

    // ─── Request PIN Reset (send email) ──────────────────────────────────────

    public function requestPinReset(Request $request): RedirectResponse
    {
        $user  = $request->user();
        $token = Str::random(64);

        // Store token → email in cache for 60 minutes
        Cache::put('pin_reset:' . hash('sha256', $token), $user->email, now()->addHour());

        Mail::to($user->email)->queue(new PinResetMail($token, $user));

        return redirect()->route('settings', ['tab' => 'transactions'])
            ->with('success', 'A PIN reset link has been sent to your email address. It expires in 60 minutes.');
    }

    // ─── Show PIN reset form (no auth needed — email link) ───────────────────

    public function showPinReset(string $token): View|RedirectResponse
    {
        $email = Cache::get('pin_reset:' . hash('sha256', $token));

        if (!$email) {
            return redirect()->route('login')
                ->withErrors(['error' => 'This PIN reset link is invalid or has expired.']);
        }

        return view('settings.pin-reset', compact('token'));
    }

    // ─── Submit PIN reset ─────────────────────────────────────────────────────

    public function resetPin(Request $request, string $token): RedirectResponse
    {
        $cacheKey = 'pin_reset:' . hash('sha256', $token);
        $email    = Cache::get($cacheKey);

        if (!$email) {
            return redirect()->route('login')
                ->withErrors(['error' => 'This PIN reset link is invalid or has expired.']);
        }

        $request->validate([
            'new_pin'     => ['required', 'digits:4'],
            'confirm_pin' => ['required', 'same:new_pin'],
        ], [
            'confirm_pin.same' => 'PIN confirmation does not match.',
        ]);

        $user = \App\Models\User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['error' => 'User account not found.']);
        }

        $user->transaction_pin = Hash::make($request->new_pin);
        $user->save();

        Cache::forget($cacheKey);

        return redirect()->route('login')
            ->with('success', 'Your transaction PIN has been reset. Please log in.');
    }

    // ─── Bank / Payout Details ────────────────────────────────────────────────

    public function updateBankDetails(Request $request): RedirectResponse
    {
        $request->validate([
            'bank_name'           => ['required', 'string', 'max:100'],
            'bank_account_number' => ['required', 'digits:10'],
            'bank_account_name'   => ['required', 'string', 'max:100'],
        ]);

        $request->user()->update($request->only('bank_name', 'bank_account_number', 'bank_account_name'));

        return redirect()->route('settings', ['tab' => 'account-details'])
            ->with('success', 'Bank details saved successfully.');
    }

    // ─── API Token ────────────────────────────────────────────────────────────

    public function generateApiToken(Request $request): RedirectResponse
    {
        $user            = $request->user();
        $user->api_token = Str::random(60);
        $user->save();

        return redirect()->route('settings', ['tab' => 'api'])
            ->with('success', 'A new API token has been generated. Keep it secret.');
    }

    // ─── Delete Account ───────────────────────────────────────────────────────

    public function deleteAccount(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'confirm_password' => ['required', 'string', function ($attr, $val, $fail) use ($user) {
                if (!Hash::check($val, $user->password)) {
                    $fail('Password is incorrect.');
                }
            }],
        ]);

        // Delete avatar from storage
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete();

        return redirect()->route('login')
            ->with('success', 'Your account has been deleted.');
    }
}
