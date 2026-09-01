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

            </main>
        </div>
    </div>

</body>
</html>
