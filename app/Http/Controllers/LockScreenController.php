<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\Binary\ByteBuffer;

class LockScreenController extends Controller
{
    /**
     * Show the lock screen.
     */
    public function show(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Force mark session as locked
        session(['session_locked' => true]);

        $user = auth()->user();
        $siteName = AppSetting::get('site_name', 'KlassPay');
        $themeColor = AppSetting::get('theme_color', '#4f46e5');
        
        // Secondary color logic or fallback
        $themeSecondary = '#06b6d4'; 

        return view('auth.lockscreen', compact('user', 'siteName', 'themeColor', 'themeSecondary'));
    }

    /**
     * Unlock session using password.
     */
    public function unlockPassword(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = auth()->user();

        if (Hash::check($request->password, $user->password)) {
            // Unlock session
            session(['session_locked' => false, 'last_activity' => now()]);
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['password' => 'Incorrect password.']);
    }

    /**
     * Generate WebAuthn biometric get (assertion) options challenge.
     */
    public function fingerprintOptions(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $credentials = $user->webauthnCredentials;
            if ($credentials->isEmpty()) {
                return response()->json(['error' => 'No biometrics registered.'], 400);
            }

            $rpName = AppSetting::get('site_name', 'KlassPay');
            $rpId = $request->getHost();

            $webAuthn = new WebAuthn($rpName, $rpId, null, true);

            // Fetch registered credential IDs as ByteBuffers
            $credentialIds = $credentials->pluck('credential_id')->map(function ($id) {
                return ByteBuffer::fromBase64Url($id);
            })->all();

            // Generate options to sign
            $getArgs = $webAuthn->getGetArgs($credentialIds, 30, true, true, true, true, true, 'preferred');

            // Store challenge in session
            session(['webauthn_challenge' => $webAuthn->getChallenge()->getHex()]);

            return response()->json($getArgs);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Verify the WebAuthn signature assertion and unlock session.
     */
    public function fingerprintVerify(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $rpName = AppSetting::get('site_name', 'KlassPay');
            $rpId = $request->getHost();

            $webAuthn = new WebAuthn($rpName, $rpId, null, true);

            // Cleanly decode Base64Url inputs
            $clientDataJSON = base64_decode(strtr($request->input('clientDataJSON'), '-_', '+/'));
            $authenticatorData = base64_decode(strtr($request->input('authenticatorData'), '-_', '+/'));
            $signature = base64_decode(strtr($request->input('signature'), '-_', '+/'));
            $id = $request->input('id');

            $credential = $user->webauthnCredentials()->where('credential_id', $id)->first();
            if (!$credential) {
                return response()->json(['error' => 'Credential ID not registered.'], 400);
            }

            $publicKey = base64_decode($credential->public_key);
            $challengeHex = session('webauthn_challenge');

            if (!$challengeHex) {
                return response()->json(['error' => 'Challenge not found in session.'], 400);
            }

            $challenge = ByteBuffer::fromHex($challengeHex);

            // Verify assertion
            $success = $webAuthn->processGet(
                $clientDataJSON,
                $authenticatorData,
                $signature,
                $publicKey,
                $challenge,
                (int)$credential->sign_count,
                false,
                true
            );

            if ($success) {
                // Update signature count to prevent replay attacks
                $credential->update([
                    'sign_count' => $webAuthn->getSignatureCounter() ?? $credential->sign_count,
                ]);

                // Unlock session
                session(['session_locked' => false, 'last_activity' => now()]);
                session()->forget('webauthn_challenge');

                return response()->json(['success' => true]);
            }

            return response()->json(['error' => 'Authentication failed.'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
