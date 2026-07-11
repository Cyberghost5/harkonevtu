@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
{{-- Welcome Row --}}
<div class="mb-6">
    <h1 class="text-xl font-bold font-outfit text-slate-800 dark:text-white">Welcome Admin,</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">This is the Admin Panel &mdash; You're the boss! &#x1F525;&#x1F451;</p>
</div>

{{-- Row 2: Banner + 2x2 Stats --}}
<div class="grid grid-cols-5 gap-5 mb-5">
    {{-- Banner Card --}}
    <div class="col-span-5 xl:col-span-3 rounded-2xl overflow-hidden relative flex flex-col justify-between" style="background:linear-gradient(135deg, {{ $themeColor }} 0%, {{ $themeSecondary ?: $themeColor }} 100%);min-height:190px;padding:28px 28px 28px 32px">
        <div class="relative z-10">
            <span class="inline-block text-[10px] font-bold px-2.5 py-1 rounded-full mb-3" style="background:rgba(255,255,255,0.2);color:#fff;letter-spacing:.08em">ADMIN PANEL</span>
            <h2 class="text-2xl font-bold font-outfit text-white leading-tight mb-1">Platform Overview</h2>
            <p class="text-sm" style="color:rgba(255,255,255,0.7)">Monitor users, transactions &amp; system health in real-time.</p>
        </div>
        <div class="flex items-end justify-between mt-6 relative z-10">
            <div>
                <p class="text-xs font-medium mb-1" style="color:rgba(255,255,255,0.6)">Total Platform Revenue</p>
                <p class="text-3xl font-extrabold font-outfit text-white">&#8358;{{ number_format($stats['total_revenue'], 2) }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs" style="color:rgba(255,255,255,0.6)">Today</p>
                <p class="text-lg font-bold text-white">&#8358;{{ number_format($stats['today_revenue'], 2) }}</p>
            </div>
        </div>
        <div class="absolute" style="width:220px;height:220px;border-radius:50%;border:1px solid rgba(255,255,255,0.07);top:-60px;right:-40px"></div>
        <div class="absolute" style="width:140px;height:140px;border-radius:50%;border:1px solid rgba(255,255,255,0.05);top:-20px;right:30px"></div>
        <div class="absolute" style="width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.08);bottom:20px;right:40px"></div>
    </div>
    {{-- 2x2 Stats --}}
    <div class="col-span-5 xl:col-span-2 grid grid-cols-2 gap-3">
        <div class="rounded-2xl p-4 flex flex-col justify-between" style="background:linear-gradient(135deg, {{ $themeColor }}, {{ $themeSecondary ?: $themeColor }});min-height:88px">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[11px] font-medium" style="color:rgba(255,255,255,0.7)">Users Balance</p>
                <div class="h-7 w-7 rounded-lg flex items-center justify-center" style="background:rgba(255,255,255,0.1)">
                    <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 13v-1"/></svg>
                </div>
            </div>
            <p class="text-xl font-bold font-outfit text-white">&#8358;{{ number_format($stats['users_balance'], 2) }}</p>
            <p class="text-[10px] text-white/70 mt-1">Total wallet funds</p>
        </div>
        <div class="rounded-2xl p-4 flex flex-col justify-between" style="background:linear-gradient(135deg, {{ $themeColor }}, {{ $themeSecondary ?: $themeColor }});min-height:88px">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[11px] font-medium" style="color:rgba(255,255,255,0.7)">Today Revenue</p>
                <div class="h-7 w-7 rounded-lg flex items-center justify-center" style="background:rgba(255,255,255,0.1)">
                    <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <p class="text-xl font-bold font-outfit text-white">&#8358;{{ number_format($stats['today_revenue'], 2) }}</p>
            <p class="text-[10px] text-white/70 mt-1">Revenue today</p>
        </div>
        <div class="rounded-2xl p-4 flex flex-col justify-between" style="background:linear-gradient(135deg, {{ $themeSecondary ?: $themeColor }}, {{ $themeColor }});min-height:88px">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[11px] font-medium" style="color:rgba(255,255,255,0.7)">Users</p>
                <div class="h-7 w-7 rounded-lg flex items-center justify-center" style="background:rgba(255,255,255,0.1)">
                    <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
            <p class="text-xl font-bold font-outfit text-white">{{ number_format($stats['total_users']) }}</p>
            <p class="text-[10px] text-white/70 mt-1">{{ $stats['active_users'] }} active</p>
        </div>
        <div class="rounded-2xl p-4 flex flex-col justify-between" style="background:linear-gradient(135deg, {{ $themeSecondary ?: $themeColor }}, {{ $themeColor }});min-height:88px">
            <div class="flex items-center justify-between mb-2">
                <p class="text-[11px] font-medium" style="color:rgba(255,255,255,0.7)">Admins</p>
                <div class="h-7 w-7 rounded-lg flex items-center justify-center" style="background:rgba(255,255,255,0.1)">
                    <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
            </div>
            <p class="text-xl font-bold font-outfit text-white">{{ number_format($stats['total_admins']) }}</p>
            <p class="text-[10px] text-white/70 mt-1">Super admins</p>
        </div>
    </div>
</div>

{{-- Row 3: Spending + Donut --}}
<div class="grid grid-cols-5 gap-5 mb-5">
    <div class="col-span-5 xl:col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6">
        <div class="flex items-start justify-between mb-1">
            <div>
                <p class="text-xs text-slate-400 dark:text-slate-500 font-medium mb-1">Total Spendings</p>
                <p class="text-3xl font-extrabold font-outfit text-slate-900 dark:text-white">&#8358;{{ number_format($stats['total_revenue'], 2) }}</p>
                <p class="text-xs font-semibold mt-0.5 text-vtu-primary">All-time platform spending</p>
            </div>
            <span class="text-xs px-2.5 py-1 rounded-full font-medium" style="background:#f0fdf4;color:#166534">Revenue</span>
        </div>
        <div class="mt-5 space-y-3">
            @php
            $services = [
                ['name'=>'Airtime',     'key'=>'airtime',     'color'=>'#6366f1'],
                ['name'=>'Data',        'key'=>'data',        'color'=>'#06b6d4'],
                ['name'=>'Electricity', 'key'=>'electricity', 'color'=>'#f59e0b'],
                ['name'=>'Cable TV',    'key'=>'cable',       'color'=>'#10b981'],
                ['name'=>'Exam Pins',   'key'=>'epins',       'color'=>'#f43f5e'],
            ];
            $totalRev = max($stats['total_revenue'], 1);
            @endphp
            @foreach($services as $svc)
            @php
                $amount = $revenueByService[$svc['key']]->total ?? 0;
                $pct = round(($amount / $totalRev) * 100);
            @endphp
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-slate-650 dark:text-slate-400">{{ $svc['name'] }}</span>
                    <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">&#8358;{{ number_format($amount, 2) }}</span>
                </div>
                <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                    <div class="h-full rounded-full" style="width:{{ $pct }}%;background:{{ $svc['color'] }}"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    <div class="col-span-5 xl:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 flex flex-col">
        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">Revenue Breakdown</p>
        @php
        $donutServices = [
            ['name'=>'Airtime',     'key'=>'airtime',     'color'=>'#6366f1'],
            ['name'=>'Data',        'key'=>'data',        'color'=>'#06b6d4'],
            ['name'=>'Electricity', 'key'=>'electricity', 'color'=>'#f59e0b'],
            ['name'=>'Cable TV',    'key'=>'cable',       'color'=>'#10b981'],
            ['name'=>'Exam Pins',   'key'=>'epins',       'color'=>'#f43f5e'],
        ];
        $circumference = 2 * M_PI * 54;
        $runningOffset = 0;
        @endphp
        <div class="flex-1 flex items-center justify-center">
            <div class="relative">
                <svg width="140" height="140" viewBox="0 0 140 140">
                    <circle cx="70" cy="70" r="54" fill="none" stroke="currentColor" class="text-slate-100 dark:text-slate-800" stroke-width="18"/>
                    @foreach($donutServices as $dsvc)
                    @php
                        $dAmount = $revenueByService[$dsvc['key']]->total ?? 0;
                        $dPct = $totalRev > 0 ? ($dAmount / $totalRev) : 0;
                        $dDash = $dPct * $circumference;
                        $dGap  = $circumference - $dDash;
                        $dRot  = -90 + ($runningOffset * 360);
                        $runningOffset += $dPct;
                    @endphp
                    @if($dDash > 0.5)
                    <circle cx="70" cy="70" r="54" fill="none"
                            stroke="{{ $dsvc['color'] }}"
                            stroke-width="18"
                            stroke-dasharray="{{ round($dDash,2) }} {{ round($dGap,2) }}"
                            transform="rotate({{ round($dRot,2) }} 70 70)"/>
                    @endif
                    @endforeach
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500">Total</p>
                    <p class="text-sm font-bold font-outfit text-slate-900 dark:text-white">&#8358;{{ number_format($stats['total_revenue']) }}</p>
                </div>
            </div>
        </div>
        <div class="mt-4 space-y-2">
            @foreach($donutServices as $dsvc)
            @php $dAmt = $revenueByService[$dsvc['key']]->total ?? 0; @endphp
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full flex-shrink-0" style="background:{{ $dsvc['color'] }}"></span>
                    <span class="text-xs text-slate-600 dark:text-slate-400">{{ $dsvc['name'] }}</span>
                </div>
                <span class="text-xs font-medium text-slate-800 dark:text-slate-200">&#8358;{{ number_format($dAmt) }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Row 4: Latest Transactions + Latest Members --}}
<div class="grid grid-cols-3 gap-5">
    <div class="col-span-3 xl:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Latest Transactions</h3>
            <a href="{{ route('admin.transactions.index') }}" class="text-xs font-medium text-vtu-primary">View all &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 px-5 py-3">Order ID</th>
                        <th class="text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 px-3 py-3">Type</th>
                        <th class="text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 px-3 py-3">User</th>
                        <th class="text-right text-[11px] font-semibold text-slate-500 dark:text-slate-400 px-3 py-3">Amount</th>
                        <th class="text-left text-[11px] font-semibold text-slate-500 dark:text-slate-400 px-3 py-3">Date</th>
                        <th class="text-center text-[11px] font-semibold text-slate-500 dark:text-slate-400 px-3 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($recentTransactions as $tx)
                    @php
                        $statusStyle = match($tx->status) {
                            'success'  => 'background:#f0fdf4;color:#166534',
                            'failed'   => 'background:#fef2f2;color:#991b1b',
                            'pending'  => 'background:#fffbeb;color:#92400e',
                            default    => 'background:#f1f5f9;color:#475569',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                        <td class="px-5 py-3 font-mono text-xs text-slate-500 dark:text-slate-400">{{ substr($tx->reference ?? (string)$tx->id, 0, 12) }}...</td>
                        <td class="px-3 py-3 text-xs font-medium text-slate-700 dark:text-slate-350 capitalize">{{ str_replace('_',' ',$tx->service_type ?? $tx->service ?? '-') }}</td>
                        <td class="px-3 py-3 text-xs text-slate-600 dark:text-slate-400">{{ optional($tx->user)->displayName() ?? '-' }}</td>
                        <td class="px-3 py-3 text-xs font-semibold text-slate-800 dark:text-slate-200 text-right">&#8358;{{ number_format($tx->amount, 2) }}</td>
                        <td class="px-3 py-3 text-xs text-slate-500 dark:text-slate-400">{{ $tx->created_at->format('d M, H:i') }}</td>
                        <td class="px-3 py-3 text-center">
                            <span class="inline-block text-[10px] font-semibold px-2 py-0.5 rounded-full capitalize" style="{{ $statusStyle }}">{{ $tx->status }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-slate-400 dark:text-slate-500">No transactions yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 flex items-center justify-between border-t border-slate-100 dark:border-slate-800">
            <p class="text-xs text-slate-400 dark:text-slate-500">Showing latest {{ count($recentTransactions) }} transactions</p>
            <a href="{{ route('admin.transactions.index') }}" class="text-xs px-3 py-1.5 rounded-lg font-medium hover:opacity-90 transition-all duration-150" style="background:rgba({{ $themeColorRgb }}, 0.1);color:{{ $themeColor }}">View All</a>
        </div>
    </div>

    <div class="col-span-3 xl:col-span-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Latest Members</h3>
            <a href="{{ route('admin.users.index') }}" class="text-xs font-medium text-vtu-primary">View all &rarr;</a>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($recentUsers as $user)
            <div class="flex items-center gap-3 px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                @if ($user->avatar)
                    <img src="{{ Storage::url($user->avatar) }}" alt="Avatar" class="h-8 w-8 rounded-2xl object-cover">
                @else
                <div class="h-9 w-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:{{ $themeColor }}">
                    {{ $user->initials() }}
                </div>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate">{{ $user->displayName() }}</p>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">Joined {{ $user->created_at->format('d M Y') }}</p>
                </div>
                <span class="flex-shrink-0 text-[10px] font-semibold px-2 py-0.5 rounded-full" style="{{ $user->is_active ? 'background:#f0fdf4;color:#166534' : 'background:#fef2f2;color:#991b1b' }}">
                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-sm text-slate-400 dark:text-slate-500">No users yet</div>
            @endforelse
        </div>
        <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('admin.users.index') }}" class="block w-full text-center text-xs py-2 rounded-lg font-medium hover:opacity-90 transition-all duration-150" style="background:rgba({{ $themeColorRgb }}, 0.1);color:{{ $themeColor }}">View All Members</a>
        </div>
    </div>
</div>
@endsection
