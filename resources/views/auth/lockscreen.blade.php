<!DOCTYPE html>
<html lang="en" class="{{ request()->cookie('theme') === 'dark' || (empty(request()->cookie('theme')) && true) ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Locked - {{ $siteName }}</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        vtu: {
                            primary: '{{ $themeColor }}',
                            secondary: '{{ $themeSecondary }}',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <style>
        .glass {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid hsla(0, 0%, 100%, 0.25);
        }
        .dark .glass {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .animate-pulse-glow {
            animation: pulse-glow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse-glow {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 {{ $themeColor }}66;
            }
            50% {
                transform: scale(1.03);
                box-shadow: 0 0 20px 8px {{ $themeSecondary }}33;
            }
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 font-sans min-h-screen flex items-center justify-center relative overflow-hidden">

    <!-- Decorative background elements -->
    <div class="absolute top-[-20%] left-[-10%] w-[60%] h-[60%] rounded-full bg-vtu-primary/10 blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] rounded-full bg-vtu-secondary/10 blur-[120px] pointer-events-none"></div>

    <div class="w-full max-w-md p-4 z-10">
        <div class="glass rounded-3xl p-8 shadow-2xl relative overflow-hidden">
            
            {{-- Dark mode indicator / lock icon --}}
            <div class="absolute top-4 right-4 text-slate-400 dark:text-slate-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>

            <div class="flex flex-col items-center">
                <!-- User Avatar -->
                <div class="relative mb-6">
                    <div class="h-24 w-24 rounded-2xl overflow-hidden ring-4 ring-white dark:ring-slate-800 shadow-lg">
                        @if ($user->avatar)
                            <img src="{{ Storage::url($user->avatar) }}" alt="Avatar" class="h-full w-full object-cover">
                        @else
                            <div class="h-full w-full flex items-center justify-center text-2xl font-bold text-white bg-gradient-to-tr from-vtu-primary to-vtu-secondary">
                                {{ $user->initials() }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Username & Status Message -->
                <h2 class="text-xl font-bold font-outfit text-slate-900 dark:text-white">{{ $user->displayName() }}</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Session Locked due to inactivity.</p>

                <!-- Feedback alert messages -->
                <div id="error-alert" class="hidden w-full mt-4 p-3.5 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs text-center"></div>

                <!-- WebAuthn Fingerprint Scanning Trigger -->
                @if($user->webauthnCredentials->isNotEmpty())
                    <div id="biometric-container" class="mt-8 flex flex-col items-center w-full">
                        <button type="button" onclick="triggerBiometricUnlock()" 
                                class="h-20 w-20 rounded-full flex items-center justify-center bg-vtu-primary text-white shadow-lg animate-pulse-glow transition-transform hover:scale-105">
                            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 009 11a5 5 0 00-10 0c0 .768.111 1.51.319 2.214m12.438-10.462A9.947 9.947 0 0114 11c0 1.259-.234 2.463-.66 3.575m0 0a3 3 0 10-4.47-4.47m3.44 2.214a13.916 13.916 0 01-2.18 7.74" />
                            </svg>
                        </button>
                        <p class="text-xs font-semibold text-slate-600 dark:text-slate-300 mt-4 cursor-pointer hover:underline" onclick="triggerBiometricUnlock()">
                            Tap here to scan fingerprint
                        </p>
                        
                        <div class="w-full flex items-center justify-center my-6">
                            <div class="border-t border-slate-200 dark:border-slate-800 w-1/4"></div>
                            <span class="mx-3 text-[10px] uppercase font-bold text-slate-400">or use password</span>
                            <div class="border-t border-slate-200 dark:border-slate-800 w-1/4"></div>
                        </div>
                    </div>
                @endif

                <!-- Password Fallback Form -->
                <form id="password-form" method="POST" action="{{ route('lockscreen.unlock') }}" class="w-full mt-4">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <div class="relative">
                                <input type="password" name="password" id="password" required placeholder="Enter password to unlock"
                                       class="w-full px-4 py-3 text-sm border border-slate-200 dark:border-slate-800 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-vtu-primary/30 focus:border-vtu-primary transition-colors pr-10">
                                <button type="button" onclick="togglePasswordVisibility()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                    <svg id="eye-icon" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-xs text-rose-500 text-center">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="w-full py-3 rounded-xl text-sm font-bold text-white transition-opacity hover:opacity-90 flex items-center justify-center gap-2"
                                style="background: {{ $themeColor }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </svg>
                            Unlock Session
                        </button>
                    </div>
                </form>

                <!-- Sign out / Switch Account link -->
                <div class="mt-8">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 font-semibold transition-colors flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Log into a different account
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- WebAuthn Biometric verification logic -->
    <script>
        function togglePasswordVisibility() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />';
            } else {
                pwd.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
            }
        }

        function showAlert(msg) {
            const box = document.getElementById('error-alert');
            box.textContent = msg;
            box.classList.remove('hidden');
        }

        // WebAuthn base64url arraybuffer conversion helpers
        function bufferToBase64url(buffer) {
            const bytes = new Uint8Array(buffer);
            let binary = '';
            for (let i = 0; i < bytes.byteLength; i++) {
                binary += String.fromCharCode(bytes[i]);
            }
            return btoa(binary)
                .replace(/\+/g, '-')
                .replace(/\//g, '_')
                .replace(/=/g, '');
        }

        function base64urlToBuffer(base64url) {
            let base64 = base64url
                .replace(/-/g, '+')
                .replace(/_/g, '/');
            while (base64.length % 4) {
                base64 += '=';
            }
            const binary = atob(base64);
            const buffer = new ArrayBuffer(binary.length);
            const bytes = new Uint8Array(buffer);
            for (let i = 0; i < binary.length; i++) {
                bytes[i] = binary.charCodeAt(i);
            }
            return buffer;
        }

        // Auto-run biometrics on load if user has registered credentials
        document.addEventListener('DOMContentLoaded', function() {
            @if($user->webauthnCredentials->isNotEmpty())
                setTimeout(triggerBiometricUnlock, 600);
            @endif
        });

        async function triggerBiometricUnlock() {
            try {
                const alertBox = document.getElementById('error-alert');
                alertBox.classList.add('hidden');

                // 1. Fetch options challenge from lockscreen endpoint
                const response = await fetch('/lockscreen/fingerprint/options', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const data = await response.json();
                if (data.error) {
                    throw new Error(data.error);
                }

                // 2. Decode standard WebAuthn get binary parameters
                const options = data.publicKey;
                options.challenge = base64urlToBuffer(options.challenge);
                
                if (options.allowCredentials) {
                    options.allowCredentials = options.allowCredentials.map(cred => {
                        cred.id = base64urlToBuffer(cred.id);
                        return cred;
                    });
                }

                // 3. Prompt user's fingerprint/Touch ID using WebAuthn
                const credential = await navigator.credentials.get({
                    publicKey: options
                });

                if (!credential) {
                    throw new Error('Biometric recognition failed or was cancelled.');
                }

                // 4. Encode signature assertion results to send to backend
                const assertion = {
                    id: credential.id,
                    clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
                    authenticatorData: bufferToBase64url(credential.response.authenticatorData),
                    signature: bufferToBase64url(credential.response.signature)
                };

                // 5. Send assertion verification request
                const verifyResponse = await fetch('/lockscreen/fingerprint/verify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(assertion)
                });

                const verifyData = await verifyResponse.json();
                if (verifyData.success) {
                    // Success! Redirect to home page
                    window.location.href = "{{ route('dashboard') }}";
                } else {
                    throw new Error(verifyData.error || 'Verification failed.');
                }

            } catch (err) {
                console.error(err);
                if (err.name !== 'NotAllowedError') { // Suppress cancel log warnings
                    showAlert(err.message || 'Biometric authentication error. Please use your password.');
                }
            }
        }
    </script>
</body>
</html>
