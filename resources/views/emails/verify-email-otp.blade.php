@extends('emails.layout')

@section('subject', 'Verify Your Email Address')
@section('title', 'Email Verification Code')

@section('body')
@php
    $_theme    = \App\Models\AppSetting::get('theme_color', '#22c55e');
    $_siteName = \App\Models\AppSetting::get('site_name', config('app.name'));
@endphp
<p style="color:#455056;font-size:15px;line-height:24px;margin:0;text-align:left">
    Hello <strong>{{ $user->displayName() }}</strong>,
</p>
<br>
<p style="color:#455056;font-size:15px;line-height:24px;margin:0;text-align:left">
    Thanks for creating an account with <strong>{{ $_siteName }}</strong>!
    Your 6-digit email verification code is:
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
    Enter this code in your mobile app to complete your registration.
    This code expires in <strong>15 minutes</strong>. Do not share it with anyone.
</p>
<br>
<p style="color:#455056;font-size:15px;line-height:24px;margin:0;text-align:left">
    <strong>Why receive this email?</strong>
    We take security very seriously and we want to keep you in the loop of activities on your account.
</p>
@endsection
