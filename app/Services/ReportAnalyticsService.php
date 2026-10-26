<?php

namespace App\Services;

use App\Models\AirtimeToCashRequest;
use App\Models\AppSetting;
use App\Models\FundingRequest;
use App\Models\ServiceTransaction;
use App\Models\User;
use App\Models\UserLoginLog;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportAnalyticsService
{
    /**
     * Resolve date range from a preset string or custom dates.
     */
    public static function resolveDateRange(string $preset = 'this_week', ?string $from = null, ?string $to = null): array
    {
        $now = Carbon::now();

        switch ($preset) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end   = $now->copy()->endOfDay();
                $label = 'Today (' . $start->format('d M Y') . ')';
                break;

            case 'yesterday':
                $start = $now->copy()->subDay()->startOfDay();
                $end   = $now->copy()->subDay()->endOfDay();
                $label = 'Yesterday (' . $start->format('d M Y') . ')';
                break;

            case 'last_week':
                $start = $now->copy()->subWeek()->startOfWeek();
                $end   = $now->copy()->subWeek()->endOfWeek();
                $label = 'Last Week (' . $start->format('d M') . ' - ' . $end->format('d M Y') . ')';
                break;

            case 'this_month':
                $start = $now->copy()->startOfMonth();
                $end   = $now->copy()->endOfDay();
                $label = 'This Month (' . $start->format('F Y') . ')';
                break;

            case 'last_month':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end   = $now->copy()->subMonth()->endOfMonth();
                $label = 'Last Month (' . $start->format('F Y') . ')';
                break;

            case 'custom':
                $start = $from ? Carbon::parse($from)->startOfDay() : $now->copy()->startOfWeek();
                $end   = $to   ? Carbon::parse($to)->endOfDay()     : $now->copy()->endOfDay();
                $label = 'Custom Range (' . $start->format('d M Y') . ' - ' . $end->format('d M Y') . ')';
                break;

            case 'this_week':
            default:
                $preset = 'this_week';
                $start  = $now->copy()->startOfWeek();
                $end    = $now->copy()->endOfDay();
                $label  = 'This Week (' . $start->format('d M') . ' - ' . $end->format('d M Y') . ')';
                break;
        }

        return [
            'preset' => $preset,
            'start'  => $start,
            'end'    => $end,
            'label'  => $label,
        ];
    }

    /**
     * Compute platform-wide executive KPIs for the given date range.
     */
    public static function getPlatformOverview(Carbon $start, Carbon $end): array
    {
        // ── 1. Wallet Funding (Platform Inflow) ──────────────────────────────
        $fundingQuery = WalletTransaction::where('type', 'credit')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'success')
            ->where('reference', 'not like', 'REF_%')
            ->where('reference', 'not like', 'REFUND_%')
            ->where('description', 'not like', 'Refund%');

        $totalFundedVolume = (float) (clone $fundingQuery)->sum('amount');
        $totalFundedCount  = (int) (clone $fundingQuery)->count();
        $avgDeposit        = $totalFundedCount > 0 ? ($totalFundedVolume / $totalFundedCount) : 0.0;

        // Funding transaction charges collected by the platform
        $fundingCharges = (float) (clone $fundingQuery)
            ->whereNotNull('metadata->charge')
            ->get()
            ->sum(fn ($tx) => (float) ($tx->metadata['charge'] ?? 0));

        // ── 2. Bill Purchases & Services (Platform Outflow) ───────────────────
        $serviceQuery = ServiceTransaction::whereBetween('created_at', [$start, $end]);

        $totalServiceVolume = (float) (clone $serviceQuery)->sum('amount');
        $totalServiceCount  = (int) (clone $serviceQuery)->count();

        $successServiceTx = (clone $serviceQuery)->where('status', 'success');
        $successCount     = (int) (clone $successServiceTx)->count();
        $successVolume    = (float) (clone $successServiceTx)->sum('amount');

        $failedCount      = (int) (clone $serviceQuery)->where('status', 'failed')->count();
        $refundedCount    = (int) (clone $serviceQuery)->where('status', 'refunded')->count();
        $pendingCount     = (int) (clone $serviceQuery)->where('status', 'pending')->count();

        $successRate = $totalServiceCount > 0 ? round(($successCount / $totalServiceCount) * 100, 1) : 100.0;

        // ── 3. Airtime to Cash Performance ──────────────────────────────────
        $a2cQuery = AirtimeToCashRequest::whereBetween('created_at', [$start, $end]);
        $a2cCount = (int) (clone $a2cQuery)->count();
        $a2cApproved = (clone $a2cQuery)->where('status', 'approved');
        $a2cVolume   = (float) (clone $a2cApproved)->sum('amount');
        $a2cProfit   = (float) (clone $a2cApproved)->sum('charge');

        // ── 4. Estimated Gross Earnings / Margins ───────────────────────────
        // Platform earnings = Bill retail margin (~2.5% avg) + Gateway fees collected + A2C charges
        $billMargin = round($successVolume * 0.025, 2);
        $totalEstimatedProfit = round($billMargin + $fundingCharges + $a2cProfit, 2);

        // ── 5. User Activity & Growth Metrics ────────────────────────────────
        $totalUsers = User::where('is_admin', false)->count();
        $newSignups = User::where('is_admin', false)->whereBetween('created_at', [$start, $end])->count();
        $agentCount = User::where('is_admin', false)->where('user_type', 'agent')->count();
        $normalCount = max(0, $totalUsers - $agentCount);

        // Active Users: Users who bought services, funded wallet, or logged in
        $serviceUserIds = ServiceTransaction::whereBetween('created_at', [$start, $end])->pluck('user_id')->toArray();
        $fundingUserIds = WalletTransaction::where('type', 'credit')->whereBetween('created_at', [$start, $end])->pluck('user_id')->toArray();
        $loginUserIds   = UserLoginLog::whereBetween('created_at', [$start, $end])->whereNotNull('user_id')->pluck('user_id')->toArray();

        $activeUserIds = array_unique(array_filter(array_merge($serviceUserIds, $fundingUserIds, $loginUserIds)));
        $activeUsersCount = count($activeUserIds);
        $inactiveUsersCount = max(0, $totalUsers - $activeUsersCount);
        $activeUserPercentage = $totalUsers > 0 ? round(($activeUsersCount / $totalUsers) * 100, 1) : 0.0;

        return [
            'period_label'          => self::resolveDateRange('this_week')['label'],
            'total_inflow'          => $totalFundedVolume,
            'total_inflow_count'    => $totalFundedCount,
            'avg_deposit'           => $avgDeposit,
            'funding_fees'          => $fundingCharges,
            'total_outflow'         => $totalServiceVolume,
            'total_outflow_count'   => $totalServiceCount,
            'success_volume'        => $successVolume,
            'success_count'         => $successCount,
            'failed_count'          => $failedCount,
            'refunded_count'        => $refundedCount,
            'pending_count'         => $pendingCount,
            'success_rate'          => $successRate,
            'net_flow'              => round($totalFundedVolume - $successVolume, 2),
            'bill_margin'           => $billMargin,
            'a2c_count'             => $a2cCount,
            'a2c_volume'            => $a2cVolume,
            'a2c_profit'            => $a2cProfit,
            'estimated_profit'      => $totalEstimatedProfit,
            'total_users'           => $totalUsers,
            'new_signups'           => $newSignups,
            'active_users'          => $activeUsersCount,
            'inactive_users'        => $inactiveUsersCount,
            'active_user_pct'       => $activeUserPercentage,
            'agents_count'          => $agentCount,
            'normal_users_count'    => $normalCount,
        ];
    }

    /**
     * Get breakdown by VTU service type with sorting support.
     */
    public static function getServiceBreakdown(Carbon $start, Carbon $end, string $sortBy = 'volume', string $direction = 'desc'): array
    {
        $rawServices = ServiceTransaction::whereBetween('created_at', [$start, $end])
            ->select(
                'service_type',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(amount) as total_volume'),
                DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count'),
                DB::raw('SUM(CASE WHEN status = "refunded" THEN 1 ELSE 0 END) as refunded_count')
            )
            ->groupBy('service_type')
            ->get();

        $services = [];
        $totalVolumeAll = 0.0;

        foreach ($rawServices as $row) {
            $totalVolumeAll += (float) $row->total_volume;
        }

        $serviceTitles = [
            'airtime'     => ['name' => 'Airtime VTU', 'icon' => '📞', 'color' => 'amber'],
            'data'        => ['name' => 'Data Bundles', 'icon' => '📶', 'color' => 'indigo'],
            'cable'       => ['name' => 'Cable TV', 'icon' => '📺', 'color' => 'purple'],
            'electricity' => ['name' => 'Electricity Bills', 'icon' => '💡', 'color' => 'emerald'],
            'epin'        => ['name' => 'Exam PINs (WAEC/NECO)', 'icon' => '🎓', 'color' => 'cyan'],
        ];

        foreach ($rawServices as $row) {
            $type = $row->service_type;
            $titleInfo = $serviceTitles[$type] ?? ['name' => ucfirst($type), 'icon' => '⚡', 'color' => 'slate'];
            $vol = (float) $row->total_volume;
            $cnt = (int) $row->total_count;
            $succ = (int) $row->success_count;
            $rate = $cnt > 0 ? round(($succ / $cnt) * 100, 1) : 100.0;
            $margin = round($vol * 0.025, 2); // ~2.5% platform margin
            $share = $totalVolumeAll > 0 ? round(($vol / $totalVolumeAll) * 100, 1) : 0.0;

            $services[] = [
                'type'         => $type,
                'name'         => $titleInfo['name'],
                'icon'         => $titleInfo['icon'],
                'color'        => $titleInfo['color'],
                'count'        => $cnt,
                'volume'       => $vol,
                'success_count'=> $succ,
                'failed_count' => (int) $row->failed_count,
                'refunded_count' => (int) $row->refunded_count,
                'success_rate' => $rate,
                'margin'       => $margin,
                'share_pct'    => $share,
            ];
        }

        // Add Airtime to Cash as a service row
        $a2cApproved = AirtimeToCashRequest::whereBetween('created_at', [$start, $end])->where('status', 'approved')->get();
        if ($a2cApproved->isNotEmpty()) {
            $a2cVol = (float) $a2cApproved->sum('amount');
            $a2cCnt = $a2cApproved->count();
            $a2cCharge = (float) $a2cApproved->sum('charge');
            $services[] = [
                'type'         => 'a2c',
                'name'         => 'Airtime to Cash',
                'icon'         => '🔄',
                'color'        => 'rose',
                'count'        => $a2cCnt,
                'volume'       => $a2cVol,
                'success_count'=> $a2cCnt,
                'failed_count' => 0,
                'refunded_count' => 0,
                'success_rate' => 100.0,
                'margin'       => $a2cCharge,
                'share_pct'    => $totalVolumeAll > 0 ? round(($a2cVol / $totalVolumeAll) * 100, 1) : 0.0,
            ];
        }

        // Sort collection
        usort($services, function ($a, $b) use ($sortBy, $direction) {
            $valA = $a[$sortBy] ?? $a['volume'];
            $valB = $b[$sortBy] ?? $b['volume'];

            if ($valA == $valB) return 0;
            if ($direction === 'asc') {
                return $valA < $valB ? -1 : 1;
            }
            return $valA > $valB ? -1 : 1;
        });

        return $services;
    }

    /**
     * Get wallet funding channels / gateway breakdown with sorting.
     */
    public static function getGatewayBreakdown(Carbon $start, Carbon $end, string $sortBy = 'volume', string $direction = 'desc'): array
    {
        $transactions = WalletTransaction::where('type', 'credit')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'success')
            ->where('reference', 'not like', 'REF_%')
            ->where('reference', 'not like', 'REFUND_%')
            ->get();

        $groups = [
            'payscribe'      => ['name' => 'PayScribe Checkout (Card/Link)', 'icon' => '💳', 'count' => 0, 'volume' => 0.0, 'fees' => 0.0],
            'payscribe_dva'  => ['name' => 'PayScribe Virtual Accounts (DVA)', 'icon' => '🏦', 'count' => 0, 'volume' => 0.0, 'fees' => 0.0],
            'paystack'       => ['name' => 'Paystack Checkout (Card)',       'icon' => '💳', 'count' => 0, 'volume' => 0.0, 'fees' => 0.0],
            'paystack_dva'   => ['name' => 'Paystack Virtual Accounts (DVA)', 'icon' => '🏦', 'count' => 0, 'volume' => 0.0, 'fees' => 0.0],
            'monnify_dva'    => ['name' => 'Monnify Virtual Accounts (DVA)',  'icon' => '🏦', 'count' => 0, 'volume' => 0.0, 'fees' => 0.0],
            'flutterwave'    => ['name' => 'Flutterwave Checkout (Card)',     'icon' => '💳', 'count' => 0, 'volume' => 0.0, 'fees' => 0.0],
            'flutterwave_dva'=> ['name' => 'Flutterwave Virtual Accounts',   'icon' => '🏦', 'count' => 0, 'volume' => 0.0, 'fees' => 0.0],
            'manual'         => ['name' => 'Manual Bank Transfer (Proof)',    'icon' => '📝', 'count' => 0, 'volume' => 0.0, 'fees' => 0.0],
            'admin'          => ['name' => 'Admin Manual Adjustment',         'icon' => '🛡️', 'count' => 0, 'volume' => 0.0, 'fees' => 0.0],
        ];

        foreach ($transactions as $tx) {
            $gw = strtolower((string) ($tx->metadata['gateway'] ?? ''));
            $desc = strtolower((string) $tx->description);
            $amt = (float) $tx->amount;
            $fee = (float) ($tx->metadata['charge'] ?? 0);

            if (str_contains($gw, 'payscribe_dva') || str_contains($desc, 'payscribe_dva')) {
                $k = 'payscribe_dva';
            } elseif (str_contains($gw, 'payscribe') || str_contains($desc, 'payscribe')) {
                $k = 'payscribe';
            } elseif (str_contains($gw, 'paystack_dva') || str_contains($desc, 'paystack_dva')) {
                $k = 'paystack_dva';
            } elseif (str_contains($gw, 'paystack') || str_contains($desc, 'paystack')) {
                $k = 'paystack';
            } elseif (str_contains($gw, 'monnify') || str_contains($desc, 'monnify')) {
                $k = 'monnify_dva';
            } elseif (str_contains($gw, 'flutterwave_dva') || str_contains($desc, 'flutterwave_dva')) {
                $k = 'flutterwave_dva';
            } elseif (str_contains($gw, 'flutterwave') || str_contains($desc, 'flutterwave')) {
                $k = 'flutterwave';
            } elseif (str_starts_with($tx->reference, 'ADM-') || str_contains($desc, '[admin]')) {
                $k = 'admin';
            } else {
                $k = 'manual';
            }

            if (!isset($groups[$k])) {
                $groups[$k] = ['name' => ucfirst($k), 'icon' => '💳', 'count' => 0, 'volume' => 0.0, 'fees' => 0.0];
            }

            $groups[$k]['count']++;
            $groups[$k]['volume'] += $amt;
            $groups[$k]['fees']   += $fee;
        }

        $list = [];
        $totalVol = array_sum(array_column($groups, 'volume'));

        foreach ($groups as $key => $item) {
            if ($item['count'] === 0) continue;
            $item['key']       = $key;
            $item['share_pct'] = $totalVol > 0 ? round(($item['volume'] / $totalVol) * 100, 1) : 0.0;
            $list[] = $item;
        }

        usort($list, function ($a, $b) use ($sortBy, $direction) {
            $valA = $a[$sortBy] ?? $a['volume'];
            $valB = $b[$sortBy] ?? $b['volume'];

            if ($valA == $valB) return 0;
            if ($direction === 'asc') return $valA < $valB ? -1 : 1;
            return $valA > $valB ? -1 : 1;
        });

        return $list;
    }

    /**
     * Get top active / transacting users in the period with sorting support.
     */
    public static function getTopUsers(Carbon $start, Carbon $end, int $limit = 15, string $sortBy = 'total_spent', string $direction = 'desc'): array
    {
        $topTx = ServiceTransaction::whereBetween('created_at', [$start, $end])
            ->where('status', 'success')
            ->select('user_id', DB::raw('COUNT(*) as tx_count'), DB::raw('SUM(amount) as total_spent'))
            ->groupBy('user_id')
            ->with(['user.wallet'])
            ->get();

        $rows = [];
        foreach ($topTx as $item) {
            $u = $item->user;
            if (!$u) continue;

            $rows[] = [
                'user_id'     => $u->id,
                'name'        => $u->name,
                'email'       => $u->email,
                'phone'       => $u->phone,
                'user_type'   => $u->user_type,
                'initials'    => $u->initials(),
                'tx_count'    => (int) $item->tx_count,
                'total_spent' => (float) $item->total_spent,
                'balance'     => (float) ($u->wallet?->balance ?? 0),
                'joined'      => $u->created_at->format('d M Y'),
                'joined_ts'   => $u->created_at->timestamp,
            ];
        }

        usort($rows, function ($a, $b) use ($sortBy, $direction) {
            $valA = $a[$sortBy] ?? $a['total_spent'];
            $valB = $b[$sortBy] ?? $b['total_spent'];

            if ($valA == $valB) return 0;
            if ($direction === 'asc') return $valA < $valB ? -1 : 1;
            return $valA > $valB ? -1 : 1;
        });

        return array_slice($rows, 0, $limit);
    }

    /**
     * Get daily trend metrics for chart or progression tables.
     */
    public static function getDailyTrends(Carbon $start, Carbon $end): array
    {
        $days = [];
        $cursor = $start->copy()->startOfDay();
        $limitEnd = $end->copy()->endOfDay();

        while ($cursor->lte($limitEnd)) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd   = $cursor->copy()->endOfDay();
            $dateKey  = $cursor->format('Y-m-d');

            $funded = (float) WalletTransaction::where('type', 'credit')
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->where('status', 'success')
                ->where('reference', 'not like', 'REF_%')
                ->sum('amount');

            $spent = (float) ServiceTransaction::whereBetween('created_at', [$dayStart, $dayEnd])
                ->where('status', 'success')
                ->sum('amount');

            $txCount = ServiceTransaction::whereBetween('created_at', [$dayStart, $dayEnd])->count();

            $days[] = [
                'date'     => $cursor->format('D, d M'),
                'date_key' => $dateKey,
                'funded'   => $funded,
                'spent'    => $spent,
                'count'    => $txCount,
            ];

            $cursor->addDay();
        }

        return $days;
    }
}
