@extends('emails.layout')

@section('subject', 'Reset Your Password')
@section('title', 'Reset Your Password')

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
    We received a request to reset your <strong>{{ $_siteName }}</strong> account password.
    Click the button below to choose a new password. This link expires in <strong>60 minutes</strong>.
</p>
<br>
<div style="text-align:center;margin:8px 0 24px">
    <a href="{{ $url }}"
       style="background:{{ $_theme }};color:#ffffff;padding:14px 36px;border-radius:8px;
              text-decoration:none;font-size:16px;font-weight:700;display:inline-block;
              letter-spacing:0.3px;font-family:Arial,Helvetica,sans-serif">
        Reset Password
    </a>
</div>
<p style="color:#455056;font-size:13px;line-height:22px;margin:0;text-align:left">
    If the button doesn&apos;t work, copy and paste this URL into your browser:
</p>
<p style="color:#455056;font-size:13px;line-height:22px;margin:6px 0 0;text-align:left;word-break:break-all">
    <a href="{{ $url }}" style="color:{{ $_theme }}">{{ $url }}</a>
</p>
<br>
<p style="color:#455056;font-size:15px;line-height:24px;margin:0;text-align:left">
    If you did not request a password reset, no further action is required.
</p>
<br>
<p style="color:#455056;font-size:15px;line-height:24px;margin:0;text-align:left">
    <strong>Why receive this email?</strong>
    We take security very seriously and we want to keep you in the loop of activities on your account.
</p>
@endsection
