<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive System Report - {{ $range['label'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #047857;
            --primary-light: #ecfdf5;
            --dark: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --bg-card: #f8fafc;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--dark);
            background: #ffffff;
            font-size: 13px;
            line-height: 1.5;
            padding: 24px;
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* Top Action Bar (hidden on print) */
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f1f5f9;
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            border: 1px solid var(--border);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-outline {
            background: white;
            color: var(--dark);
            border: 1px solid var(--border);
        }

        /* Report Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 18px;
            margin-bottom: 20px;
        }

        .brand-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--dark);
            letter-spacing: -0.5px;
        }

        .brand-subtitle {
            font-size: 13px;
            color: var(--primary);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        .meta-box {
            text-align: right;
            font-size: 12px;
            color: var(--muted);
        }

        .period-badge {
            display: inline-block;
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            margin-bottom: 4px;
            border: 1px solid #a7f3d0;
        }

        /* KPI Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .kpi-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 14px;
        }

        .kpi-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .kpi-value {
            font-size: 19px;
            font-weight: 800;
            color: var(--dark);
        }

        .kpi-sub {
            font-size: 11px;
            color: var(--muted);
            margin-top: 2px;
        }

        /* Section Headings */
        .section-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--dark);
            margin: 22px 0 10px 0;
            padding-bottom: 6px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 12px;
        }

        th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-align: left;
            padding: 8px 10px;
            border: 1px solid var(--border);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        td {
            padding: 7px 10px;
            border: 1px solid var(--border);
            vertical-align: middle;
        }

        tr:nth-child(even) td {
            background: #fafafa;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-semibold { font-weight: 600; }
        .font-bold { font-weight: 700; }
        .text-success { color: #047857; }
        .text-danger { color: #b91c1c; }
        .text-muted { color: #64748b; }

        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .footer {
            margin-top: 32px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
            font-size: 11px;
            color: var(--muted);
            display: flex;
            justify-content: space-between;
        }

        @media print {
            body {
                padding: 0;
                font-size: 11px;
            }
            .actions-bar {
                display: none !important;
            }
            .kpi-value {
                font-size: 16px;
            }
            .header {
                padding-bottom: 12px;
                margin-bottom: 14px;
            }
            .section-title {
                margin: 16px 0 8px 0;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>

<div class="container">
    {{-- Action Bar (Browser Only) --}}
    <div class="actions-bar">
        <div>
            <strong>Executive Printable Report</strong> &middot; <span class="text-muted">{{ $range['label'] }}</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button class="btn btn-outline" onclick="window.close()">Close</button>
            <button class="btn btn-primary" onclick="window.print()">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print / Save PDF
            </button>
        </div>
    </div>

    {{-- Report Header --}}
    <div class="header">
        <div>
            <div class="brand-title">{{ config('app.name', 'VTU Platform') }}</div>
            <div class="brand-subtitle">Executive Performance & System Analytics</div>
            <div style="font-size: 12px; color: var(--muted); margin-top: 4px;">
                Automated platform revenue, liquidity, service utilization and customer activity audit.
            </div>
        </div>
        <div class="meta-box">
            <div class="period-badge">{{ $range['label'] }}</div>
            <div><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</div>
            <div><strong>Window:</strong> {{ $range['start']->format('M d, Y') }} - {{ $range['end']->format('M d, Y') }}</div>
        </div>
    </div>

    {{-- Executive KPIs --}}
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Total Inflow (Wallet Funding)</div>
            <div class="kpi-value mono text-success">&#8358;{{ number_format($overview['total_inflow'], 2) }}</div>
            <div class="kpi-sub">{{ number_format($overview['total_inflow_count']) }} deposits completed (Avg: &#8358;{{ number_format($overview['avg_deposit'], 0) }})</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">Total Outflow (Bills Consumed)</div>
            <div class="kpi-value mono text-danger">&#8358;{{ number_format($overview['total_outflow'], 2) }}</div>
            <div class="kpi-sub">{{ number_format($overview['success_count']) }} successful ({{ $overview['success_rate'] }}% rate)</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">Estimated Gross Margin</div>
            <div class="kpi-value mono">&#8358;{{ number_format($overview['estimated_profit'], 2) }}</div>
            <div class="kpi-sub">Discounts + gateway fees + A2C margins</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">Active Transacting Users</div>
            <div class="kpi-value mono">{{ number_format($overview['active_users']) }}</div>
            <div class="kpi-sub">{{ $overview['active_user_pct'] }}% of {{ number_format($overview['total_users']) }} total registered</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">New Signups in Period</div>
            <div class="kpi-value mono">+{{ number_format($overview['new_signups']) }}</div>
            <div class="kpi-sub">Total platform users: {{ number_format($overview['total_users']) }}</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">Net Liquidity Inflow</div>
            <div class="kpi-value mono {{ $overview['net_flow'] >= 0 ? 'text-success' : 'text-danger' }}">
                &#8358;{{ number_format($overview['net_flow'], 2) }}
            </div>
            <div class="kpi-sub">{{ $overview['net_flow'] >= 0 ? 'Surplus Inflow' : 'Net Liquidity Deficit' }}</div>
        </div>
    </div>

    {{-- Service Performance Breakdown --}}
    <div class="section-title">
        <span>Service Line Breakdown</span>
        <span style="font-size: 11px; text-transform: none; color: var(--muted);">{{ count($services) }} services tracked</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Service Name</th>
                <th class="text-center">Transactions</th>
                <th class="text-right">Total Volume</th>
                <th class="text-center">Success Rate</th>
                <th class="text-right">Gross Margin</th>
                <th class="text-right">Share %</th>
            </tr>
        </thead>
        <tbody>
            @forelse($services as $svc)
            <tr>
                <td class="font-bold">{{ $svc['name'] }}</td>
                <td class="text-center">{{ number_format($svc['count']) }}</td>
                <td class="text-right font-semibold mono">&#8358;{{ number_format($svc['volume'], 2) }}</td>
                <td class="text-center mono">{{ $svc['success_rate'] }}%</td>
                <td class="text-right font-semibold text-success mono">&#8358;{{ number_format($svc['margin'], 2) }}</td>
                <td class="text-right text-muted mono">{{ $svc['share_pct'] }}%</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-muted" style="padding: 16px;">No service transaction records in selected period.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="two-col">
        {{-- Funding Gateway Distribution --}}
        <div>
            <div class="section-title">
                <span>Payment Gateways</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Gateway Channel</th>
                        <th class="text-center">Count</th>
                        <th class="text-right">Inflow Volume</th>
                        <th class="text-right">Fees Collected</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gateways as $gw)
                    <tr>
                        <td class="font-semibold">{{ $gw['name'] }}</td>
                        <td class="text-center">{{ number_format($gw['count']) }}</td>
                        <td class="text-right mono font-semibold">&#8358;{{ number_format($gw['volume'], 2) }}</td>
                        <td class="text-right mono text-muted">&#8358;{{ number_format($gw['fees'], 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No funding records.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- System Health Summary --}}
        <div>
            <div class="section-title">
                <span>System Liquidity & Balances</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th class="text-right">Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Overall Transaction Success Rate</td>
                        <td class="text-right mono font-bold text-success">{{ $overview['success_rate'] }}%</td>
                    </tr>
                    <tr>
                        <td>Failed / Unsuccessful Attempts</td>
                        <td class="text-right mono text-danger font-semibold">{{ number_format($overview['failed_count']) }}</td>
                    </tr>
                    <tr>
                        <td>Refunded Purchases</td>
                        <td class="text-right mono text-muted">{{ number_format($overview['refunded_count']) }}</td>
                    </tr>
                    <tr>
                        <td>Active User Engagement Rate</td>
                        <td class="text-right mono font-semibold">{{ $overview['active_user_pct'] }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Top Transacting Customers --}}
    <div class="section-title">
        <span>Top Customers By Transaction Volume</span>
        <span style="font-size: 11px; text-transform: none; color: var(--muted);">Leaderboard</span>
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th>Customer Name</th>
                <th>Email Address</th>
                <th>Phone Number</th>
                <th class="text-center">Transactions</th>
                <th class="text-right">Volume Consumed</th>
                <th class="text-right">Wallet Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topUsers as $idx => $u)
            <tr>
                <td class="text-center font-bold text-muted">{{ $idx + 1 }}</td>
                <td class="font-bold">{{ $u['name'] }}</td>
                <td class="text-muted">{{ $u['email'] }}</td>
                <td class="mono text-muted">{{ $u['phone'] ?: '—' }}</td>
                <td class="text-center mono">{{ number_format($u['tx_count']) }}</td>
                <td class="text-right font-bold mono text-danger">&#8358;{{ number_format($u['total_spent'], 2) }}</td>
                <td class="text-right mono">&#8358;{{ number_format($u['balance'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted" style="padding: 16px;">No user activity recorded in this period.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div>Generated by {{ config('app.name', 'VTU Platform') }} Analytics Engine</div>
        <div>Confidential &middot; Internal Platform Audit Report</div>
    </div>
</div>

</body>
</html>
