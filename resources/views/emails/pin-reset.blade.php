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
