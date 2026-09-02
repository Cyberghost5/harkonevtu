<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $siteName }} – API Reference & Documentation</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Fira+Code:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"Fira Code"', 'monospace'],
                    }
                }
            }
        }
    </script>
    <style type="text/css">
        .glass-panel {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen">

    <!-- Header / Navbar -->
    <header class="sticky top-0 z-50 bg-slate-900/90 backdrop-blur border-b border-slate-800 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="/" class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-sm font-extrabold">API</span>
                <span>{{ $siteName }} <span class="text-indigo-400 font-mono text-xs px-2 py-0.5 rounded bg-indigo-500/10 border border-indigo-500/20">v1.0</span></span>
            </a>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs font-mono text-slate-400 bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-700">
                Base URL: <code class="text-emerald-400 font-semibold">{{ $baseUrl }}</code>
            </span>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- Sidebar Navigation -->
            <aside class="lg:col-span-1">
                <div class="sticky top-24 space-y-6 glass-panel p-5 rounded-2xl">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Getting Started</h3>
                        <ul class="space-y-2 text-sm font-medium text-slate-300">
                            <li><a href="#overview" class="hover:text-indigo-400 transition-colors block">Overview & Conventions</a></li>
                            <li><a href="#headers" class="hover:text-indigo-400 transition-colors block">Headers & Auth</a></li>
                            <li><a href="#response-format" class="hover:text-indigo-400 transition-colors block">Standard Response Format</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-400 mb-3">Milestone 1: Authentication</h3>
                        <ul class="space-y-2 text-xs font-mono text-slate-300">
                            <li><a href="#post-register" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /auth/register</a></li>
                            <li><a href="#post-login" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /auth/login</a></li>
                            <li><a href="#post-verify-otp" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /auth/verify-otp</a></li>
                            <li><a href="#post-resend-otp" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /auth/resend-otp</a></li>
                            <li><a href="#post-forgot-password" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /auth/forgot-password</a></li>
                            <li><a href="#post-reset-password" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /auth/reset-password</a></li>
                            <li><a href="#get-me" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400 font-bold">GET</span> /auth/me</a></li>
                            <li><a href="#post-logout" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /auth/logout</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-400 mb-3">Milestone 2: User Account</h3>
                        <ul class="space-y-2 text-xs font-mono text-slate-300">
                            <li><a href="#get-user-profile" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400 font-bold">GET</span> /user/profile</a></li>
                            <li><a href="#put-user-profile" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-400 font-bold">PUT</span> /user/profile</a></li>
                            <li><a href="#put-user-password" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-400 font-bold">PUT</span> /user/password</a></li>
                            <li><a href="#put-user-pin" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-400 font-bold">PUT</span> /user/pin</a></li>
                            <li><a href="#post-user-pin-verify" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /user/pin/verify</a></li>
                            <li><a href="#put-user-bank" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-400 font-bold">PUT</span> /user/bank</a></li>
                            <li><a href="#post-upgrade-agent" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /user/upgrade-agent</a></li>
                            <li><a href="#post-dva-generate" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /user/dva/generate</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-400 mb-3">Milestone 3: Airtime & Data</h3>
                        <ul class="space-y-2 text-xs font-mono text-slate-300">
                            <li><a href="#get-airtime-networks" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400 font-bold">GET</span> /airtime/networks</a></li>
                            <li><a href="#post-network-lookup" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /airtime/network-lookup</a></li>
                            <li><a href="#post-airtime-purchase" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /airtime/purchase</a></li>
                            <li><a href="#get-data-networks" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400 font-bold">GET</span> /data/networks</a></li>
                            <li><a href="#post-data-plans" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /data/plans</a></li>
                            <li><a href="#post-data-purchase" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /data/purchase</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-400 mb-3">Milestone 4: Bills & Utilities</h3>
                        <ul class="space-y-2 text-xs font-mono text-slate-300">
                            <li><a href="#get-electricity-discos" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400 font-bold">GET</span> /bills/electricity/discos</a></li>
                            <li><a href="#post-validate-meter" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /bills/electricity/validate-meter</a></li>
                            <li><a href="#post-electricity-purchase" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /bills/electricity/purchase</a></li>
                            <li><a href="#get-cable-providers" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400 font-bold">GET</span> /bills/cable/providers</a></li>
                            <li><a href="#post-cable-plans" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /bills/cable/plans</a></li>
                            <li><a href="#post-validate-smartcard" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /bills/cable/validate-card</a></li>
                            <li><a href="#post-cable-purchase" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /bills/cable/purchase</a></li>
                            <li><a href="#get-exam-types" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400 font-bold">GET</span> /bills/exam-pins/types</a></li>
                            <li><a href="#post-exam-purchase" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /bills/exam-pins/purchase</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-400 mb-3">Milestone 5: Wallet & Payments</h3>
                        <ul class="space-y-2 text-xs font-mono text-slate-300">
                            <li><a href="#get-wallet-balance" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400 font-bold">GET</span> /wallet/balance</a></li>
                            <li><a href="#get-wallet-transactions" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400 font-bold">GET</span> /wallet/transactions</a></li>
                            <li><a href="#post-initialize-payment" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /payments/initialize</a></li>
                            <li><a href="#post-verify-payment" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /payments/verify</a></li>
                            <li><a href="#get-dva-accounts" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400 font-bold">GET</span> /payments/dva-accounts</a></li>
                            <li><a href="#post-manual-request" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /payments/manual-request</a></li>
                            <li><a href="#post-redeem-coupon" class="hover:text-indigo-400 flex items-center gap-2"><span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-bold">POST</span> /payments/redeem-coupon</a></li>
                        </ul>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="lg:col-span-3 space-y-12">
                
                <!-- Overview Section -->
                <section id="overview" class="glass-panel p-8 rounded-3xl">
                    <h1 class="text-3xl font-extrabold text-white mb-4">API Overview & Conventions</h1>
                    <p class="text-slate-300 text-sm leading-relaxed mb-6">
                        Welcome to the official <strong>{{ $siteName }} RESTful API Reference</strong>. All requests should be sent over HTTPS to the base URL listed below. Responses are formatted exclusively in UTF-8 JSON.
                    </p>

                    <div id="headers" class="bg-slate-900/90 p-5 rounded-xl border border-slate-800 mb-6 font-mono text-xs text-slate-300 space-y-2">
                        <div class="text-slate-500">// Required Request Headers for all Endpoints</div>
                        <div><span class="text-indigo-400">Accept:</span> application/json</div>
                        <div><span class="text-indigo-400">Content-Type:</span> application/json</div>
                        <div><span class="text-indigo-400">Authorization:</span> Bearer &lt;sanctum_token&gt; <span class="text-slate-500">(For protected routes)</span></div>
                    </div>

                    <div id="response-format">
                        <h2 class="text-xl font-bold text-white mb-3">Standardized Response Format</h2>
                        <p class="text-slate-400 text-xs mb-4">
                            All API responses strictly enforce a boolean <code class="text-emerald-400 font-mono">status</code> key (<code class="text-emerald-400">true</code> for success, <code class="text-rose-400">false</code> for error/validation failures).
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Success Template -->
                            <div class="bg-slate-900 p-4 rounded-xl border border-emerald-500/30">
                                <div class="text-xs font-bold text-emerald-400 uppercase mb-2">Success Structure (200 / 201)</div>
                                <pre class="text-xs font-mono text-slate-300 overflow-x-auto"><code>{
  "status": true,
  "message": "Operation successful.",
  "data": { ... }
}</code></pre>
                            </div>

                            <!-- Error Template -->
                            <div class="bg-slate-900 p-4 rounded-xl border border-rose-500/30">
                                <div class="text-xs font-bold text-rose-400 uppercase mb-2">Error Structure (400 / 401 / 422)</div>
                                <pre class="text-xs font-mono text-slate-300 overflow-x-auto"><code>{
  "status": false,
  "message": "Validation or error message.",
  "errors": { ... }
}</code></pre>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Milestone 1 Endpoints -->
                <div class="space-y-8">
                    <h2 class="text-2xl font-bold text-white tracking-tight border-b border-slate-800 pb-3">
                        Milestone 1: Authentication Endpoints
                    </h2>

                    <!-- 1. POST /auth/register -->
                    <div id="post-register" class="glass-panel p-6 rounded-2xl border-l-4 border-emerald-500 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/auth/register</code>
                            </div>
                            <span class="text-xs text-slate-400 bg-slate-800 px-2.5 py-1 rounded">Guest Access</span>
                        </div>
                        <p class="text-slate-300 text-xs">Registers a new user account, creates their wallet, and returns an initial Sanctum Bearer token.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Request Body (JSON)</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "08012345678",
  "password": "Password123",
  "password_confirmation": "Password123",
  "referral_code": "OPTIONAL"
}</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">201 Created Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "Registration successful.",
  "data": {
    "token": "1|sanctum_token_string",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "08012345678",
      "wallet": { "balance": "0.00" }
    }
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- 2. POST /auth/login -->
                    <div id="post-login" class="glass-panel p-6 rounded-2xl border-l-4 border-emerald-500 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/auth/login</code>
                            </div>
                            <span class="text-xs text-slate-400 bg-slate-800 px-2.5 py-1 rounded">Guest Access</span>
                        </div>
                        <p class="text-slate-300 text-xs">Authenticates user using email, phone, or username. Returns Sanctum token or triggers OTP flow if enabled.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Request Body (JSON)</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>{
  "login": "john@example.com",
  "password": "Password123"
}</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">200 OK Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "Login successful.",
  "data": {
    "requires_otp": false,
    "token": "2|sanctum_token_string",
    "user": { ... }
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- 3. POST /auth/verify-otp -->
                    <div id="post-verify-otp" class="glass-panel p-6 rounded-2xl border-l-4 border-emerald-500 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/auth/verify-otp</code>
                            </div>
                            <span class="text-xs text-slate-400 bg-slate-800 px-2.5 py-1 rounded">Guest Access</span>
                        </div>
                        <p class="text-slate-300 text-xs">Verifies 6-digit SMS OTP for accounts requiring 2FA login verification.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Request Body (JSON)</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>{
  "user_id": 1,
  "otp": "123456"
}</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">200 OK Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "OTP verified successfully.",
  "data": {
    "token": "3|sanctum_token_string",
    "user": { ... }
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- 4. POST /auth/resend-otp -->
                    <div id="post-resend-otp" class="glass-panel p-6 rounded-2xl border-l-4 border-emerald-500 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/auth/resend-otp</code>
                            </div>
                            <span class="text-xs text-slate-400 bg-slate-800 px-2.5 py-1 rounded">Guest Access</span>
                        </div>
                        <p class="text-slate-300 text-xs">Resends a fresh 6-digit SMS OTP to the user's registered phone number.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Request Body (JSON)</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>{
  "user_id": 1
}</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">200 OK Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "A new OTP code has been sent to your phone number."
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- 5. GET /auth/me -->
                    <div id="get-me" class="glass-panel p-6 rounded-2xl border-l-4 border-blue-500 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-blue-500/20 text-blue-400 font-mono font-bold text-xs">GET</span>
                                <code class="text-base font-bold text-white font-mono">/auth/me</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Fetches current authenticated user profile and live wallet balance.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Headers Required</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>Authorization: Bearer <sanctum_token>
Accept: application/json</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">200 OK Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "User profile fetched successfully.",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "wallet": {
      "balance": "15000.00",
      "total_funded": "50000.00"
    }
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- 6. POST /auth/logout -->
                    <div id="post-logout" class="glass-panel p-6 rounded-2xl border-l-4 border-emerald-500 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/auth/logout</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Revokes the current Sanctum access token.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Headers Required</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>Authorization: Bearer <sanctum_token>
Accept: application/json</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">200 OK Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "Logged out successfully."
}</code></pre>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Milestone 2: User Profile & Account Management Section -->
                <div id="milestone-2" class="space-y-6">
                    <h2 class="text-2xl font-bold text-white font-outfit border-b border-slate-800 pb-3 flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full bg-indigo-500 animate-pulse"></span>
                        Milestone 2: User Profile & Account Management APIs
                    </h2>

                    <!-- 1. GET /user/profile -->
                    <div id="get-user-profile" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-blue-500/20 text-blue-400 font-mono font-bold text-xs">GET</span>
                                <code class="text-base font-bold text-white font-mono">/user/profile</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Fetch full profile details, user tier, referral code, PIN set status, and live wallet balance.</p>
                        <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "User profile retrieved successfully.",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "username": "johndoe",
      "email": "john@example.com",
      "phone": "08031234567",
      "user_type": "user",
      "has_pin": true,
      "referral_code": "REF12345"
    },
    "wallet": {
      "balance": "5000.00",
      "total_funded": "5000.00"
    }
  }
}</code></pre>
                    </div>

                    <!-- 2. PUT /user/profile -->
                    <div id="put-user-profile" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-amber-500/20 text-amber-400 font-mono font-bold text-xs">PUT</span>
                                <code class="text-base font-bold text-white font-mono">/user/profile</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Update account full name, email, or phone number.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Request Payload</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>{
  "name": "John Updated Doe",
  "email": "johnnew@example.com",
  "phone": "08031234567"
}</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">200 OK Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "Profile updated successfully.",
  "data": { ... }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- 3. PUT /user/password -->
                    <div id="put-user-password" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-amber-500/20 text-amber-400 font-mono font-bold text-xs">PUT</span>
                                <code class="text-base font-bold text-white font-mono">/user/password</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Change account password by providing current password and new password confirmation.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Request Payload</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>{
  "current_password": "OldPassword123",
  "password": "NewPassword123",
  "password_confirmation": "NewPassword123"
}</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">200 OK Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "Password changed successfully."
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- 4. PUT /user/pin -->
                    <div id="put-user-pin" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-amber-500/20 text-amber-400 font-mono font-bold text-xs">PUT</span>
                                <code class="text-base font-bold text-white font-mono">/user/pin</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Set a initial 4-digit transaction PIN or update existing transaction PIN.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Request Payload</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>{
  "current_pin": "1234",
  "pin": "5678",
  "pin_confirmation": "5678"
}</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">200 OK Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "Transaction PIN updated successfully."
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- 5. POST /user/pin/verify -->
                    <div id="post-user-pin-verify" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/user/pin/verify</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Verify user's 4-digit transaction PIN before authorizing sensitive actions.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Request Payload</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>{
  "pin": "1234"
}</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">200 OK Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "Transaction PIN is valid."
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- 6. PUT /user/bank -->
                    <div id="put-user-bank" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-amber-500/20 text-amber-400 font-mono font-bold text-xs">PUT</span>
                                <code class="text-base font-bold text-white font-mono">/user/bank</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Update withdrawal bank account details.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Request Payload</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>{
  "bank_name": "Kuda Bank",
  "account_number": "2012345678",
  "account_name": "John Doe"
}</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">200 OK Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "Bank details updated successfully."
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- 7. POST /user/upgrade-agent -->
                    <div id="post-upgrade-agent" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/user/upgrade-agent</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Upgrade user tier to Agent status (deducts configured agent fee from wallet balance).</p>
                    </div>

                    <!-- 8. POST /user/dva/generate -->
                    <div id="post-dva-generate" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/user/dva/generate</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Generate or re-query Dedicated Virtual Bank Accounts (DVA) for automated wallet top-up.</p>
                    </div>
                </div>

                <!-- Milestone 3: Airtime & Data Services Section -->
                <div id="milestone-3" class="space-y-6">
                    <h2 class="text-2xl font-bold text-white font-outfit border-b border-slate-800 pb-3 flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full bg-vtu-primary animate-pulse"></span>
                        Milestone 3: Airtime & Data Services APIs
                    </h2>

                    <!-- GET /airtime/networks -->
                    <div id="get-airtime-networks" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-blue-500/20 text-blue-400 font-mono font-bold text-xs">GET</span>
                                <code class="text-base font-bold text-white font-mono">/airtime/networks</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Fetch list of active telco networks and current discount/commission rates.</p>
                        <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "Airtime networks retrieved successfully.",
  "data": {
    "user_tier": "User",
    "networks": [
      { "id": 1, "name": "MTN", "network_key": "mtn", "discount_percentage": 2.0, "enabled": true }
    ]
  }
}</code></pre>
                    </div>

                    <!-- POST /airtime/network-lookup -->
                    <div id="post-network-lookup" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/airtime/network-lookup</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Auto-detect network provider from recipient phone number prefix.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Request Payload</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>{
  "phone": "08031234567"
}</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">200 OK Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "Network detected successfully.",
  "data": {
    "phone": "08031234567",
    "prefix": "0803",
    "network_key": "mtn",
    "name": "MTN"
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- POST /airtime/purchase -->
                    <div id="post-airtime-purchase" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/airtime/purchase</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Disburse VTU airtime to recipient phone number after validating 4-digit PIN and wallet balance.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Request Payload</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>{
  "network": "mtn",
  "phone": "08031234567",
  "amount": 500,
  "pin": "1234"
}</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">200 OK Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "₦500 MTN airtime sent to 08031234567 successfully.",
  "data": {
    "reference": "AIR20260902100000ABC123",
    "network": "MTN",
    "recipient": "08031234567",
    "face_amount": 500,
    "charged_amount": 490,
    "discount_applied": 10,
    "balance_after": 4510.50
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- GET /data/networks -->
                    <div id="get-data-networks" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-blue-500/20 text-blue-400 font-mono font-bold text-xs">GET</span>
                                <code class="text-base font-bold text-white font-mono">/data/networks</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Fetch data networks and enabled data types (SME, Gifting, Corporate Gifting, Awoof).</p>
                    </div>

                    <!-- POST /data/plans -->
                    <div id="post-data-plans" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/data/plans</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Fetch data plans for a network, sorted from lowest to highest price with pricing calculated for the user tier.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Request Payload</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>{
  "network": "mtn",
  "data_type": "sme"
}</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">200 OK Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "Data plans retrieved successfully.",
  "data": {
    "network": "mtn",
    "total_plans": 10,
    "plans": [
      { "id": 1, "plan_name": "1GB SME", "validity": "30 Days", "price": 280 }
    ]
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- POST /data/purchase -->
                    <div id="post-data-purchase" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/data/purchase</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Purchase data bundle after validating 4-digit transaction PIN and wallet balance.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Request Payload</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>{
  "phone": "08031234567",
  "plan_id": 1,
  "pin": "1234"
}</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">200 OK Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "MTN 1GB SME sent to 08031234567 successfully.",
  "data": {
    "reference": "DAT20260902100000XYZ789",
    "network": "MTN",
    "recipient": "08031234567",
    "plan_name": "1GB SME",
    "amount_paid": 280,
    "balance_after": 4230.50
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Milestone 4: Bills & Utilities Services Section -->
                <div id="milestone-4" class="space-y-6">
                    <h2 class="text-2xl font-bold text-white font-outfit border-b border-slate-800 pb-3 flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full bg-amber-500 animate-pulse"></span>
                        Milestone 4: Bills & Utilities Services APIs
                    </h2>

                    <!-- GET /bills/electricity/discos -->
                    <div id="get-electricity-discos" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-blue-500/20 text-blue-400 font-mono font-bold text-xs">GET</span>
                                <code class="text-base font-bold text-white font-mono">/bills/electricity/discos</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Fetch list of active Electricity Distribution Companies (IKEDC, EKEDC, AEDC, KEDCO, IBEDC, PHED, etc.).</p>
                    </div>

                    <!-- POST /bills/electricity/validate-meter -->
                    <div id="post-validate-meter" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/bills/electricity/validate-meter</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Validate meter number with Disco provider and return customer name & address.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Request Payload</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>{
  "disco_id": 1,
  "meter_type": "prepaid",
  "meter_number": "11111111111"
}</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">200 OK Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "Meter validated successfully.",
  "data": {
    "disco_id": 1,
    "disco_name": "Ikeja Electric",
    "meter_number": "11111111111",
    "customer_name": "JOHN DOE"
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- POST /bills/electricity/purchase -->
                    <div id="post-electricity-purchase" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/bills/electricity/purchase</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Pay electricity bill or generate prepaid token after validating PIN and wallet balance.</p>
                    </div>

                    <!-- GET /bills/cable/providers -->
                    <div id="get-cable-providers" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-blue-500/20 text-blue-400 font-mono font-bold text-xs">GET</span>
                                <code class="text-base font-bold text-white font-mono">/bills/cable/providers</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">List active Cable TV providers (DSTV, GOTV, Startimes).</p>
                    </div>

                    <!-- POST /bills/cable/plans -->
                    <div id="post-cable-plans" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/bills/cable/plans</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Fetch subscription plans for a Cable provider.</p>
                    </div>

                    <!-- POST /bills/cable/validate-card -->
                    <div id="post-validate-smartcard" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/bills/cable/validate-card</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Validate IUC / Smartcard number and return subscriber customer name.</p>
                    </div>

                    <!-- POST /bills/cable/purchase -->
                    <div id="post-cable-purchase" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/bills/cable/purchase</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Renew Cable TV subscription for validated Smartcard.</p>
                    </div>

                    <!-- GET /bills/exam-pins/types -->
                    <div id="get-exam-types" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-blue-500/20 text-blue-400 font-mono font-bold text-xs">GET</span>
                                <code class="text-base font-bold text-white font-mono">/bills/exam-pins/types</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">List available exam scratch card types (WAEC, NECO, NABTEB, JAMB) and unit prices.</p>
                    </div>

                    <!-- POST /bills/exam-pins/purchase -->
                    <div id="post-exam-purchase" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/bills/exam-pins/purchase</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Purchase exam scratch card tokens and receive pin/serial numbers.</p>
                    </div>

                </div>

                <!-- Milestone 5: Wallet & Payments Services Section -->
                <div id="milestone-5" class="space-y-6">
                    <h2 class="text-2xl font-bold text-white font-outfit border-b border-slate-800 pb-3 flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></span>
                        Milestone 5: Wallet & Payments Services APIs
                    </h2>

                    <!-- GET /wallet/balance -->
                    <div id="get-wallet-balance" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-blue-500/20 text-blue-400 font-mono font-bold text-xs">GET</span>
                                <code class="text-base font-bold text-white font-mono">/wallet/balance</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Retrieve current live wallet balance, total funded amount, and total spent amount.</p>
                        <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "Wallet balance retrieved successfully.",
  "data": {
    "balance": 15000.00,
    "total_funded": 20000.00,
    "total_spent": 5000.00,
    "formatted": "₦15,000.00"
  }
}</code></pre>
                    </div>

                    <!-- GET /wallet/transactions -->
                    <div id="get-wallet-transactions" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-blue-500/20 text-blue-400 font-mono font-bold text-xs">GET</span>
                                <code class="text-base font-bold text-white font-mono">/wallet/transactions</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Fetch paginated transaction history with optional filters for type (`credit`/`debit`) and service_type (`airtime`, `data`, `electricity`, `cable`, `epin`, `funding`, `refund`).</p>
                    </div>

                    <!-- POST /payments/initialize -->
                    <div id="post-initialize-payment" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/payments/initialize</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Initialize online card or bank transfer wallet funding via Paystack / Monnify.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">Request Payload</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-slate-300 border border-slate-800"><code>{
  "amount": 1000,
  "gateway": "paystack"
}</code></pre>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-400 uppercase mb-1">200 OK Response</div>
                                <pre class="bg-slate-900 p-3 rounded-lg text-xs font-mono text-emerald-400 border border-slate-800"><code>{
  "status": true,
  "message": "Payment initialized successfully.",
  "data": {
    "gateway": "paystack",
    "reference": "PAY20260902104803NPMVZM7B",
    "authorization_url": "https://checkout.paystack.com/...",
    "amount": 1000
  }
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- POST /payments/verify -->
                    <div id="post-verify-payment" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/payments/verify</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Verify status of online payment transaction after user completes checkout.</p>
                    </div>

                    <!-- GET /payments/dva-accounts -->
                    <div id="get-dva-accounts" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-blue-500/20 text-blue-400 font-mono font-bold text-xs">GET</span>
                                <code class="text-base font-bold text-white font-mono">/payments/dva-accounts</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Retrieve assigned Dedicated Virtual Bank Accounts (Monnify/Paystack) for automated bank transfer top-up.</p>
                    </div>

                    <!-- POST /payments/manual-request -->
                    <div id="post-manual-request" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/payments/manual-request</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Submit manual bank deposit notification to administrator for review and approval.</p>
                    </div>

                    <!-- POST /payments/redeem-coupon -->
                    <div id="post-redeem-coupon" class="glass-panel p-6 rounded-2xl space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 font-mono font-bold text-xs">POST</span>
                                <code class="text-base font-bold text-white font-mono">/payments/redeem-coupon</code>
                            </div>
                            <span class="text-xs text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-1 rounded font-mono">Bearer Token Required</span>
                        </div>
                        <p class="text-slate-300 text-xs">Redeem promo code / voucher coupon to credit user wallet instantly.</p>
                    </div>

                </div>

            </main>
        </div>
    </div>

</body>
</html>
