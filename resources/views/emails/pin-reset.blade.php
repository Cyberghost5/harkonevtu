@extends('emails.layout')

@section('subject', 'Reset Your Transaction PIN')
@section('title', 'Reset Your Transaction PIN')

@section('body')
<p style="color:#455056;font-size:15px;line-height:24px;margin:0;text-align:left">
    Hello <strong>{{ $user->displayName() }}</strong>,
</p>
<br>
<p style="color:#455056;font-size:15px;line-height:24px;margin:0;text-align:left">
    We received a request to reset your transaction PIN.
    Click the button below to set a brand-new PIN. This link expires in <strong>60 minutes</strong>.
</p>
<br>
<div style="text-align:center;margin:8px 0 24px">
    <a href="{{ $resetUrl }}"
       style="background:{{ $_theme }};color:#ffffff;padding:14px 36px;border-radius:8px;
              text-decoration:none;font-size:16px;font-weight:700;display:inline-block;
              letter-spacing:0.3px;font-family:Arial,Helvetica,sans-serif">
        Reset My PIN
    </a>
</div>
<p style="color:#455056;font-size:13px;line-height:22px;margin:0;text-align:left">
    If the button doesn&apos;t work, copy and paste this URL into your browser:
</p>
<p style="color:#455056;font-size:13px;line-height:22px;margin:6px 0 0;text-align:left;word-break:break-all">
    <a href="{{ $resetUrl }}" style="color:{{ $_theme }}">{{ $resetUrl }}</a>
</p>
<br>
<p style="color:#455056;font-size:15px;line-height:24px;margin:0;text-align:left">
    <strong>Why receive this email?</strong>
    If you didn&apos;t request a PIN reset, you can safely ignore this email. Your PIN will remain unchanged.
</p>
@endsection

  body { margin:0; padding:0; background:#f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrap { max-width:580px; margin:32px auto; }
  .card { background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,.08); }
  .header { padding:32px 40px 24px; text-align:center; border-bottom:1px solid #f1f5f9; }
  .header img { height:40px; }
  .body { padding:32px 40px; }
  h1 { margin:0 0 8px; font-size:22px; font-weight:700; color:#0f172a; }
  p { margin:0 0 16px; font-size:15px; color:#475569; line-height:1.6; }
  .btn-wrap { text-align:center; margin:28px 0; }
  .btn { display:inline-block; padding:14px 36px; background:#{{ ltrim($themeColor ?? '22c55e', '#') }}; color:#fff; text-decoration:none; border-radius:12px; font-size:16px; font-weight:700; letter-spacing:.3px; }
  .divider { border:none; border-top:1px solid #e2e8f0; margin:24px 0; }
  .url-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 16px; font-size:13px; color:#64748b; word-break:break-all; margin-bottom:16px; }
  .footer { padding:20px 40px; background:#f8fafc; text-align:center; font-size:12px; color:#94a3b8; }
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="header">
      <strong style="font-size:20px;color:#0f172a;">{{ config('app.name') }}</strong>
    </div>
    <div class="body">
      <h1>Reset Your Transaction PIN</h1>
      <p>Hi <strong>{{ $user->displayName() }}</strong>,</p>
      <p>
        We received a request to reset your transaction PIN.
        Click the button below to set a new PIN. This link expires in <strong>60 minutes</strong>.
      </p>
      <div class="btn-wrap">
        <a href="{{ $resetUrl }}" class="btn">Reset My PIN</a>
      </div>
      <hr class="divider">
      <p style="font-size:13px;color:#64748b;">If the button doesn't work, copy and paste this link into your browser:</p>
      <div class="url-box">{{ $resetUrl }}</div>
      <p style="font-size:13px;color:#94a3b8;">
        If you didn't request a PIN reset, you can safely ignore this email.
        Your PIN will remain unchanged.
      </p>
    </div>
    <div class="footer">
      &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </div>
  </div>
</div>
</body>
</html>
