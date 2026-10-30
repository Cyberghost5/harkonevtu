@extends('emails.layout')

@section('subject', $emailSubject)
@section('title', $emailSubject)

@section('body')
@php
    $_theme    = \App\Models\AppSetting::get('theme_color', '#22c55e');
    $_siteName = \App\Models\AppSetting::get('site_name', config('app.name'));
@endphp

{{-- Personalised greeting --}}
@if(!empty($recipientName))
<p style="color:#455056;font-size:15px;line-height:24px;margin:0;text-align:left">
    Hello <strong>{{ $recipientName }}</strong>,
</p>
<br>
@endif

{{-- Main body — each paragraph separated by blank lines is rendered as its own <p> --}}
@foreach(array_filter(array_map('trim', preg_split('/\n{2,}/', $emailBody))) as $paragraph)
<p style="color:#455056;font-size:15px;line-height:24px;margin:0 0 12px;text-align:left">
    {!! nl2br(e($paragraph)) !!}
</p>
@endforeach

{{-- Optional CTA button --}}
@if(!empty($actionUrl) && !empty($actionLabel))
<br>
<div style="text-align:center;margin:8px 0 24px">
    <a href="{{ $actionUrl }}"
       style="background:{{ $_theme }};color:#ffffff;padding:14px 36px;border-radius:8px;
              text-decoration:none;font-size:16px;font-weight:700;display:inline-block;
              letter-spacing:0.3px;font-family:Arial,Helvetica,sans-serif">
        {{ $actionLabel }}
    </a>
</div>
<p style="color:#455056;font-size:13px;line-height:22px;margin:0;text-align:left">
    If the button doesn&apos;t work, copy and paste this URL into your browser:
</p>
<p style="color:#455056;font-size:13px;line-height:22px;margin:6px 0 0;text-align:left;word-break:break-all">
    <a href="{{ $actionUrl }}" style="color:{{ $_theme }}">{{ $actionUrl }}</a>
</p>
<br>
@endif

{{-- Footer note --}}
<p style="color:#455056;font-size:13px;line-height:22px;margin:16px 0 0;text-align:left">
    <strong>Why receive this email?</strong>
    You are receiving this because you have an active account on <strong>{{ $_siteName }}</strong>.
</p>

@endsection
