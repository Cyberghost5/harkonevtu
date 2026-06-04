@extends('emails.layout')

@section('subject', 'Verify Your Email Address')
@section('title', 'Verify Your Email Address')

@section('body')
<p style="color:#455056;font-size:15px;line-height:24px;margin:0;text-align:left">
    Hello <strong>{{ $user->displayName() }}</strong>,
</p>
<br>
<p style="color:#455056;font-size:15px;line-height:24px;margin:0;text-align:left">
    Thanks for creating an account with <strong>{{ $_siteName }}</strong>!
    Please click the button below to verify your email address and activate your account.
</p>
<br>
<div style="text-align:center;margin:8px 0 24px">
    <a href="{{ $url }}"
       style="background:{{ $_theme }};color:#ffffff;padding:14px 36px;border-radius:8px;
              text-decoration:none;font-size:16px;font-weight:700;display:inline-block;
              letter-spacing:0.3px;font-family:Arial,Helvetica,sans-serif">
        Verify Email Address
    </a>
</div>
<p style="color:#455056;font-size:13px;line-height:22px;margin:0;text-align:left">
    This link expires in <strong>60 minutes</strong>.
    If the button doesn&apos;t work, copy and paste this URL into your browser:
</p>
<p style="color:#455056;font-size:13px;line-height:22px;margin:6px 0 0;text-align:left;word-break:break-all">
    <a href="{{ $url }}" style="color:{{ $_theme }}">{{ $url }}</a>
</p>
<br>
<p style="color:#455056;font-size:15px;line-height:24px;margin:0;text-align:left">
    <strong>Why receive this email?</strong>
    We take security very seriously and we want to keep you in the loop of activities on your account.
</p>
@endsection
