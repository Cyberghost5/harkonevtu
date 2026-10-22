@extends('emails.layout')

@php
    $serviceName  = $alert['service'] ?? 'VTU Service';
    $providerName = strtoupper($alert['provider'] ?? 'PROVIDER');
    $amount       = number_format((float) ($alert['amount'] ?? 0), 2);
    $recipient    = $alert['recipient'] ?? 'N/A';
    $reference    = $alert['reference'] ?? 'N/A';
    $attemptedAt  = $alert['attempted_at'] ?? now()->toDayDateTimeString();

    $custName     = $alert['customer_name'] ?? 'Unknown User';
    $custEmail    = $alert['customer_email'] ?? 'N/A';
    $custPhone    = $alert['customer_phone'] ?? 'N/A';
    $custId       = $alert['customer_id'] ?? 'N/A';
    $custBalance  = number_format((float) ($alert['customer_wallet_balance'] ?? 0), 2);

    $rawError     = $alert['raw_error'] ?? 'Insufficient Funds';
    if (is_array($rawError)) {
        $rawError = json_encode($rawError, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
@endphp

@section('subject', "[URGENT] Provider Low Balance Alert: {$serviceName}")
@section('title', 'Provider Low Balance Alert')

@section('body')
{{-- Urgent Alert Banner --}}
<div style="background-color:#fef2f2;border:1px solid #fecaca;border-left:4px solid #ef4444;border-radius:6px;padding:16px 20px;text-align:left;margin-bottom:24px">
    <div style="display:flex;align-items:center;margin-bottom:6px">
        <span style="font-size:18px;font-weight:700;color:#991b1b">⚠️ Action Required: Upstream Wallet Depleted</span>
    </div>
    <p style="color:#7f1d1d;font-size:14px;line-height:22px;margin:0">
        A user attempted to complete a <strong>{{ $serviceName }}</strong> transaction, but your upstream provider (<strong>{{ $providerName }}</strong>) rejected the request due to <strong>insufficient funds/float</strong>. Please fund your provider wallet immediately.
    </p>
</div>

{{-- Transaction Summary Card --}}
<div style="text-align:left;margin-bottom:24px">
    <h3 style="color:#1e293b;font-size:16px;font-weight:700;margin:0 0 12px 0;text-transform:uppercase;letter-spacing:0.5px">
        Transaction Information
    </h3>
    <table width="100%" cellpadding="10" cellspacing="0" style="border-collapse:collapse;background:#f8fafc;border-radius:6px;font-size:14px">
        <tbody>
            <tr style="border-bottom:1px solid #e2e8f0">
                <td style="color:#64748b;font-weight:600;width:38%">Service Attempted</td>
                <td style="color:#0f172a;font-weight:700">{{ $serviceName }}</td>
            </tr>
            <tr style="border-bottom:1px solid #e2e8f0">
                <td style="color:#64748b;font-weight:600">Provider / Gateway</td>
                <td>
                    <span style="background-color:#fee2e2;color:#991b1b;padding:3px 8px;border-radius:4px;font-weight:700;font-size:12px">
                        {{ $providerName }}
                    </span>
                </td>
            </tr>
            <tr style="border-bottom:1px solid #e2e8f0">
                <td style="color:#64748b;font-weight:600">Transaction Amount</td>
                <td style="color:#ef4444;font-weight:700;font-size:16px">₦{{ $amount }}</td>
            </tr>
            <tr style="border-bottom:1px solid #e2e8f0">
                <td style="color:#64748b;font-weight:600">Recipient Target</td>
                <td style="color:#0f172a;font-family:monospace;font-size:14px">{{ $recipient }}</td>
            </tr>
            <tr style="border-bottom:1px solid #e2e8f0">
                <td style="color:#64748b;font-weight:600">Reference</td>
                <td style="color:#334155;font-family:monospace;font-size:13px">{{ $reference }}</td>
            </tr>
            <tr>
                <td style="color:#64748b;font-weight:600">Date & Time</td>
                <td style="color:#334155">{{ $attemptedAt }}</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Customer Details Card --}}
<div style="text-align:left;margin-bottom:24px">
    <h3 style="color:#1e293b;font-size:16px;font-weight:700;margin:0 0 12px 0;text-transform:uppercase;letter-spacing:0.5px">
        Customer Details
    </h3>
    <table width="100%" cellpadding="10" cellspacing="0" style="border-collapse:collapse;background:#f8fafc;border-radius:6px;font-size:14px">
        <tbody>
            <tr style="border-bottom:1px solid #e2e8f0">
                <td style="color:#64748b;font-weight:600;width:38%">Full Name</td>
                <td style="color:#0f172a;font-weight:600">{{ $custName }}</td>
            </tr>
            <tr style="border-bottom:1px solid #e2e8f0">
                <td style="color:#64748b;font-weight:600">Email Address</td>
                <td style="color:#0f172a">{{ $custEmail }}</td>
            </tr>
            <tr style="border-bottom:1px solid #e2e8f0">
                <td style="color:#64748b;font-weight:600">Phone Number</td>
                <td style="color:#0f172a">{{ $custPhone }}</td>
            </tr>
            <tr style="border-bottom:1px solid #e2e8f0">
                <td style="color:#64748b;font-weight:600">User ID</td>
                <td style="color:#334155">#{{ $custId }}</td>
            </tr>
            <tr>
                <td style="color:#64748b;font-weight:600">Customer Wallet Balance</td>
                <td style="color:#16a34a;font-weight:700">₦{{ $custBalance }}</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Provider Raw Error Message --}}
<div style="text-align:left;margin-bottom:24px">
    <h3 style="color:#1e293b;font-size:16px;font-weight:700;margin:0 0 12px 0;text-transform:uppercase;letter-spacing:0.5px">
        Raw Provider Response
    </h3>
    <div style="background-color:#1e293b;color:#f8fafc;padding:14px;border-radius:6px;font-family:Consolas,Monaco,'Courier New',monospace;font-size:13px;line-height:20px;word-break:break-all;white-space:pre-wrap">
{{ $rawError }}
    </div>
</div>

{{-- Customer Protection Note --}}
<p style="color:#64748b;font-size:13px;line-height:20px;text-align:left;margin:0 0 20px">
    <em>ℹ️ Note: The customer's wallet was not charged (or has been automatically refunded), and they received a user-friendly notice indicating a temporary gateway issue rather than seeing your backend balance details.</em>
</p>

{{-- Action Button --}}
<div style="text-align:center;margin-top:20px">
    <a href="{{ url('/admin/api-logs') }}"
       style="display:inline-block;padding:12px 28px;background-color:#dc2626;color:#ffffff;text-decoration:none;font-weight:700;border-radius:6px;font-size:14px;letter-spacing:0.3px">
        View Admin API Logs &rarr;
    </a>
</div>
@endsection
