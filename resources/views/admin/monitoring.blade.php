@extends('layouts.admin')

@section('title', 'Server Monitoring')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-outfit font-bold text-slate-900 dark:text-white">Server Monitoring</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Real-time server performance diagnostics and resource utilization analytics.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="relative flex h-3.5 w-3.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"></span>
            </span>
            <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Live System Telemetry</span>
            <span class="text-xs text-slate-400 ml-1 font-mono">({{ PHP_OS_FAMILY }} OS)</span>
        </div>
    </div>

    {{-- Main Grid --}}
    <div class="grid md:grid-cols-2 gap-6">

        {{-- ── 1. CPU PANEL ── --}}
        <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                    CPU Utilization
                </h3>
                <span id="cpu-numeric" class="text-2xl font-mono font-bold text-indigo-600 dark:text-indigo-400">0.0%</span>
            </div>

            {{-- Gauge Area --}}
            <div class="flex items-center justify-center py-2">
                <div class="relative flex items-center justify-center h-28 w-28">
                    <svg class="absolute inset-0 transform -rotate-95" viewBox="0 0 36 36">
                        <path class="text-slate-100 dark:text-slate-800" stroke-width="2.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <path id="cpu-gauge" class="text-indigo-500 transition-all duration-500 ease-out" stroke-dasharray="0, 100" stroke-width="2.8" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    </svg>
                    <div class="text-center z-10">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">LOAD</span>
                    </div>
                </div>
            </div>

            {{-- History Chart --}}
            <div class="h-44">
                <canvas id="cpu-chart"></canvas>
            </div>
        </div>

        {{-- ── 2. MEMORY PANEL ── --}}
        <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Memory / Swap Usage
                </h3>
                <span id="ram-numeric" class="text-lg font-mono font-bold text-emerald-600 dark:text-emerald-400">0 GB / 0 GB</span>
            </div>

            {{-- Progress Bars --}}
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">
                        <span>Physical Memory (RAM)</span>
                        <span id="ram-percent">0%</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
                        <div id="ram-bar" class="bg-emerald-500 h-2.5 rounded-full transition-all duration-500" style="width: 0%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">
                        <span>Swap / Page File Memory</span>
                        <span id="swap-percent">0%</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
                        <div id="swap-bar" class="bg-cyan-500 h-2.5 rounded-full transition-all duration-500" style="width: 0%"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-slate-400 dark:text-slate-500 mt-1">
                        <span id="swap-used">0 GB used</span>
                        <span id="swap-total">0 GB total</span>
                    </div>
                </div>
            </div>

            {{-- History Chart --}}
            <div class="h-44">
                <canvas id="ram-chart"></canvas>
            </div>
        </div>

        {{-- ── 3. DISK SPACE & I/O PANEL ── --}}
        <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="h-5 w-5 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4a2 2 0 012-2m16 0h-2M4 13H6m0 0v.01M20 13v.01M4 17h.01M20 17h.01M8 17h.01M12 17h.01M16 17h.01"/>
                    </svg>
                    Disk Operations & Space
                </h3>
                <span id="disk-space-text" class="text-xs font-mono font-medium text-slate-500 dark:text-slate-400">0 GB / 0 GB (0%)</span>
            </div>

            {{-- Grid of Details --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/80 p-3.5 rounded-xl text-center">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase">Read Speed</span>
                    <span id="disk-read" class="block mt-1 font-mono font-bold text-sm text-cyan-600 dark:text-cyan-400">0.00 B/s</span>
                </div>
                <div class="bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/80 p-3.5 rounded-xl text-center">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase">Write Speed</span>
                    <span id="disk-write" class="block mt-1 font-mono font-bold text-sm text-pink-500">0.00 B/s</span>
                </div>
            </div>

            {{-- Disk Space Progress Bar --}}
            <div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                    <div id="disk-space-bar" class="bg-cyan-500 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>

            {{-- History Chart --}}
            <div class="h-44">
                <canvas id="disk-chart"></canvas>
            </div>
        </div>

        {{-- ── 4. NETWORK PANEL ── --}}
        <div class="rounded-2xl bg-white dark:bg-vtu-darkCard border border-slate-200 dark:border-slate-700 shadow-sm p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="h-5 w-5 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Network Throughput
                </h3>
                <span id="network-total-text" class="text-xs font-mono font-medium text-slate-500 dark:text-slate-400">Total: 0.00 B/s</span>
            </div>

            {{-- Grid of Speed Details --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/80 p-3.5 rounded-xl text-center">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase flex items-center justify-center gap-1">
                        <svg class="h-3 w-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 13l-7 7-7-7m14-6l-7 7-7-7"/></svg>
                        Incoming (Download)
                    </span>
                    <span id="network-incoming" class="block mt-1 font-mono font-bold text-sm text-emerald-600 dark:text-emerald-400">0.00 B/s</span>
                </div>
                <div class="bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/80 p-3.5 rounded-xl text-center">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase flex items-center justify-center gap-1">
                        <svg class="h-3 w-3 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 11l7-7 7 7M5 19l7-7 7 7"/></svg>
                        Outgoing (Upload)
                    </span>
                    <span id="network-outgoing" class="block mt-1 font-mono font-bold text-sm text-indigo-500">0.00 B/s</span>
                </div>
            </div>

            {{-- Network graph --}}
            <div class="h-46">
                <canvas id="network-chart"></canvas>
            </div>
        </div>

    </div>

</div>
@endsection

@section('scripts')
{{-- Load Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const DATA_URL = "{{ route('admin.monitoring.data') }}";
    
    // Formatting helper
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0.00 B/s';
        if (bytes < 1024) return bytes.toFixed(decimals) + ' B/s';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['B/s', 'KB/s', 'MB/s', 'GB/s', 'TB/s'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
    
    function formatSize(bytes, decimals = 2) {
        if (bytes === 0) return '0 GB';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    // Chart configs
    const maxHistoryPoints = 15;
    const labels = Array(maxHistoryPoints).fill('');
    
    const chartDefaults = {
        type: 'line',
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { display: false },
                y: {
                    grid: { color: 'rgba(156, 163, 175, 0.1)' },
                    ticks: { color: '#9ca3af', font: { size: 9, family: 'monospace' } }
                }
            },
            plugins: { legend: { display: false } },
            elements: {
                point: { radius: 0 },
                line: { tension: 0.35, borderWidth: 2 }
            }
        }
    };

    // 1. CPU Chart
    const cpuCtx = document.getElementById('cpu-chart').getContext('2d');
    const cpuChart = new Chart(cpuCtx, {
        ...chartDefaults,
        data: {
            labels: [...labels],
            datasets: [{
                data: Array(maxHistoryPoints).fill(0),
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.05)',
                fill: true
            }]
        },
        options: {
            ...chartDefaults.options,
            scales: {
                ...chartDefaults.options.scales,
                y: { ...chartDefaults.options.scales.y, min: 0, max: 100 }
            }
        }
    });

    // 2. RAM Chart
    const ramCtx = document.getElementById('ram-chart').getContext('2d');
    const ramChart = new Chart(ramCtx, {
        ...chartDefaults,
        data: {
            labels: [...labels],
            datasets: [{
                data: Array(maxHistoryPoints).fill(0),
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.05)',
                fill: true
            }]
        },
        options: {
            ...chartDefaults.options,
            scales: {
                ...chartDefaults.options.scales,
                y: { ...chartDefaults.options.scales.y, min: 0, max: 100 }
            }
        }
    });

    // 3. Disk Chart (Read / Write Speeds)
    const diskCtx = document.getElementById('disk-chart').getContext('2d');
    const diskChart = new Chart(diskCtx, {
        ...chartDefaults,
        data: {
            labels: [...labels],
            datasets: [
                {
                    data: Array(maxHistoryPoints).fill(0),
                    borderColor: 'rgb(6, 182, 212)',
                    fill: false
                },
                {
                    data: Array(maxHistoryPoints).fill(0),
                    borderColor: 'rgb(244, 63, 94)',
                    fill: false
                }
            ]
        },
        options: {
            ...chartDefaults.options,
            plugins: {
                legend: {
                    display: true,
                    labels: { boxWidth: 10, boxHeight: 6, font: { size: 9 }, color: '#9ca3af' }
                }
            }
        }
    });
    diskChart.data.datasets[0].label = 'Read Speed';
    diskChart.data.datasets[1].label = 'Write Speed';

    // 4. Network Chart (Upload / Download)
    const netCtx = document.getElementById('network-chart').getContext('2d');
    const netChart = new Chart(netCtx, {
        ...chartDefaults,
        data: {
            labels: [...labels],
            datasets: [
                {
                    data: Array(maxHistoryPoints).fill(0),
                    borderColor: 'rgb(16, 185, 129)',
                    fill: false
                },
                {
                    data: Array(maxHistoryPoints).fill(0),
                    borderColor: 'rgb(99, 102, 241)',
                    fill: false
                }
            ]
        },
        options: {
            ...chartDefaults.options,
            plugins: {
                legend: {
                    display: true,
                    labels: { boxWidth: 10, boxHeight: 6, font: { size: 9 }, color: '#9ca3af' }
                }
            }
        }
    });
    netChart.data.datasets[0].label = 'Download';
    netChart.data.datasets[1].label = 'Upload';

    // Main Update Function
    async function updateDiagnostics() {
        try {
            const res = await fetch(DATA_URL);
            if (!res.ok) throw new Error('Data fetch failed');
            const data = await res.json();

            // A. Update CPU
            const cpuVal = parseFloat(data.cpu) || 0;
            document.getElementById('cpu-numeric').textContent = cpuVal.toFixed(1) + '%';
            document.getElementById('cpu-gauge').setAttribute('stroke-dasharray', `${cpuVal}, 100`);
            
            // Push values and shift arrays
            cpuChart.data.datasets[0].data.push(cpuVal);
            cpuChart.data.datasets[0].data.shift();
            cpuChart.update('none');

            // B. Update Memory
            const mem = data.memory;
            if (mem) {
                const ramPercent = Math.min(mem.percentage || 0, 100);
                document.getElementById('ram-percent').textContent = ramPercent.toFixed(1) + '%';
                document.getElementById('ram-bar').style.width = ramPercent + '%';
                document.getElementById('ram-numeric').textContent = `${formatSize(mem.used, 1)} / ${formatSize(mem.total, 1)}`;

                const swapPercent = Math.min(mem.swap_percentage || 0, 100);
                document.getElementById('swap-percent').textContent = swapPercent.toFixed(1) + '%';
                document.getElementById('swap-bar').style.width = swapPercent + '%';
                document.getElementById('swap-used').textContent = formatSize(mem.swap_used, 1) + ' used';
                document.getElementById('swap-total').textContent = formatSize(mem.swap_total, 1) + ' total';

                ramChart.data.datasets[0].data.push(ramPercent);
                ramChart.data.datasets[0].data.shift();
                ramChart.update('none');
            }

            // C. Update Disk
            const diskSpace = data.disk_space;
            const diskIO = data.disk_io;
            if (diskSpace && diskIO) {
                const spacePercent = diskSpace.percentage || 0;
                document.getElementById('disk-space-text').textContent = `${formatSize(diskSpace.used, 0)} / ${formatSize(diskSpace.total, 0)} (${spacePercent.toFixed(1)}%)`;
                document.getElementById('disk-space-bar').style.width = spacePercent + '%';
                
                document.getElementById('disk-read').textContent = formatBytes(diskIO.read_speed);
                document.getElementById('disk-write').textContent = formatBytes(diskIO.write_speed);

                // Convert bytes to MB for cleaner graph rendering scale
                diskChart.data.datasets[0].data.push((diskIO.read_speed / (1024 * 1024)) || 0);
                diskChart.data.datasets[0].data.shift();
                diskChart.data.datasets[1].data.push((diskIO.write_speed / (1024 * 1024)) || 0);
                diskChart.data.datasets[1].data.shift();
                diskChart.update('none');
            }

            // D. Update Network
            const net = data.network;
            if (net) {
                const totalNet = (parseFloat(net.incoming) || 0) + (parseFloat(net.outgoing) || 0);
                document.getElementById('network-total-text').textContent = `Total: ${formatBytes(totalNet)}`;
                document.getElementById('network-incoming').textContent = formatBytes(net.incoming);
                document.getElementById('network-outgoing').textContent = formatBytes(net.outgoing);

                netChart.data.datasets[0].data.push((net.incoming / 1024) || 0); // Convert to KB for cleaner scale
                netChart.data.datasets[0].data.shift();
                netChart.data.datasets[1].data.push((net.outgoing / 1024) || 0);
                netChart.data.datasets[1].data.shift();
                netChart.update('none');
            }

        } catch (err) {
            console.error('System diagnostics refresh failed:', err);
        }
    }

    // Run interval
    setInterval(updateDiagnostics, 2000);
    updateDiagnostics(); // Init run
</script>
@endsection
