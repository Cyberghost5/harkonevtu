<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive System & Financial Report</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" max-width="640" style="max-width: 640px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #0f172a; padding: 28px 32px; border-bottom: 3px solid #10b981;">
                            <table width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px;">{{ strtoupper($siteName) }}</h1>
                                        <p style="margin: 4px 0 0 0; font-size: 13px; color: #94a3b8;">Executive System & Financial Analytics</p>
                                    </td>
                                    <td align="right">
                                        <span style="display: inline-block; background-color: rgba(16, 185, 129, 0.15); color: #10b981; font-size: 11px; font-weight: 700; padding: 5px 12px; border-radius: 9999px; text-transform: uppercase;">
                                            {{ $periodLabel }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px;">
                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6; color: #334155;">
                                Hello Administrator,
                            </p>
                            <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #64748b;">
                                Here is the consolidated financial and system performance summary for <strong>{{ $siteName }}</strong> covering <strong>{{ $periodLabel }}</strong>. A comprehensive, audit-ready PDF document has been attached to this email.
                            </p>

                            <!-- KPI Grid -->
                            <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 24px;">
                                <tr>
                                    <!-- Inflow -->
                                    <td width="48%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #10b981; border-radius: 10px; padding: 14px 16px; vertical-align: top;">
                                        <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Total Inflow (Funded)</p>
                                        <p style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">₦{{ number_format($overview['total_inflow'], 2) }}</p>
                                        <p style="margin: 4px 0 0 0; font-size: 11px; color: #94a3b8;">{{ $overview['total_inflow_count'] }} Deposits</p>
                                    </td>
                                    <td width="4%"></td>
                                    <!-- Outflow -->
                                    <td width="48%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #6366f1; border-radius: 10px; padding: 14px 16px; vertical-align: top;">
                                        <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Total Outflow (Bills)</p>
                                        <p style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">₦{{ number_format($overview['total_outflow'], 2) }}</p>
                                        <p style="margin: 4px 0 0 0; font-size: 11px; color: #10b981; font-weight: 600;">{{ $overview['success_rate'] }}% Success Rate</p>
                                    </td>
                                </tr>
                                <tr><td height="12" colspan="3"></td></tr>
                                <tr>
                                    <!-- Profit -->
                                    <td width="48%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #f59e0b; border-radius: 10px; padding: 14px 16px; vertical-align: top;">
                                        <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Est. Gross Margin</p>
                                        <p style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">₦{{ number_format($overview['estimated_profit'], 2) }}</p>
                                        <p style="margin: 4px 0 0 0; font-size: 11px; color: #94a3b8;">Discounts + Platform Fees</p>
                                    </td>
                                    <td width="4%"></td>
                                    <!-- Active Users -->
                                    <td width="48%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #0ea5e9; border-radius: 10px; padding: 14px 16px; vertical-align: top;">
                                        <p style="margin: 0 0 4px 0; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Active Users</p>
                                        <p style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">{{ number_format($overview['active_users']) }}</p>
                                        <p style="margin: 4px 0 0 0; font-size: 11px; color: #94a3b8;">{{ $overview['active_user_pct'] }}% of {{ number_format($overview['total_users']) }} Users</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Service Summary Table -->
                            <h3 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">Top Service Breakdown</h3>
                            <table width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin-bottom: 24px;">
                                <tr style="background-color: #0f172a; color: #ffffff; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                                    <th style="padding: 10px 12px; text-align: left;">Service</th>
                                    <th style="padding: 10px 12px; text-align: center;">Transactions</th>
                                    <th style="padding: 10px 12px; text-align: right;">Volume (₦)</th>
                                    <th style="padding: 10px 12px; text-align: center;">Success</th>
                                </tr>
                                @foreach($services as $i => $s)
                                <tr style="background-color: {{ $i % 2 === 0 ? '#ffffff' : '#f8fafc' }}; font-size: 12px; border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 10px 12px; font-weight: 600; color: #1e293b;">{{ $s['name'] }}</td>
                                    <td style="padding: 10px 12px; text-align: center; color: #64748b;">{{ number_format($s['count']) }}</td>
                                    <td style="padding: 10px 12px; text-align: right; font-weight: 600; color: #0f172a;">₦{{ number_format($s['volume'], 2) }}</td>
                                    <td style="padding: 10px 12px; text-align: center; color: {{ $s['success_rate'] >= 95 ? '#10b981' : '#f59e0b' }}; font-weight: 700;">{{ $s['success_rate'] }}%</td>
                                </tr>
                                @endforeach
                            </table>

                            <!-- PDF Attachment Notice -->
                            <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 14px 18px; margin-bottom: 24px;">
                                <table width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td width="30" style="vertical-align: top; font-size: 20px;">📎</td>
                                        <td style="vertical-align: middle;">
                                            <p style="margin: 0; font-size: 13px; font-weight: 700; color: #065f46;">Full PDF Audit Report Attached</p>
                                            <p style="margin: 2px 0 0 0; font-size: 12px; color: #047857;">Open the attached PDF file to inspect individual gateway inflows, user leaderboards, and detailed system integrity logs.</p>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">
                                This automated report was dispatched according to your admin scheduled reporting settings. To adjust recipient emails or delivery frequency, visit the <strong>Reports & Analytics</strong> tab in your admin dashboard.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 32px; border-top: 1px solid #e2e8f0; text-align: center;">
                            <p style="margin: 0; font-size: 11px; color: #94a3b8;">
                                © {{ date('Y') }} {{ $siteName }}. Confidential & Proprietary Platform Administration Report.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
