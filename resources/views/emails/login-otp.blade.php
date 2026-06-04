@extends('emails.layout')

@section('subject', 'Your Login OTP – ' . ($_siteName ?? config('app.name')))
@section('title', 'Login OTP')

@section('body')
<p style="color:#455056;font-size:15px;line-height:24px;margin:0;text-align:left">
    Hello <strong>{{ $user->displayName() }}</strong>,
</p>
<br>
<p style="color:#455056;font-size:15px;line-height:24px;margin:0;text-align:left">
    Your one-time login code for <strong>{{ $_siteName }}</strong> is:
</p>
<br>
<div style="text-align:center;margin:8px 0 24px">
    <span style="display:inline-block;font-size:38px;font-weight:700;letter-spacing:0.3em;
                 color:#1e1e2d;background:#f2f3f8;padding:16px 32px;border-radius:8px;
                 font-family:Arial,Helvetica,sans-serif">
        {{ $otp }}
    </span>
</div>
<p style="color:#455056;font-size:15px;line-height:24px;margin:0;text-align:left">
    This code expires in <strong>10 minutes</strong>. Do not share it with anyone.
</p>
<br>
<p style="color:#455056;font-size:15px;line-height:24px;margin:0;text-align:left">
    <strong>Why receive this email?</strong>
    We take security very seriously and we want to keep you in the loop of activities on your account.
</p>
@endsection
