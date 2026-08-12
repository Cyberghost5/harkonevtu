<?php

// Load Composer Autoloader
require __DIR__ . '/../vendor/autoload.php';

// Load Laravel Bootstrap
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

$logs = DB::table('api_logs')
    ->select('service', 'provider', DB::raw('count(*) as total'))
    ->groupBy('service', 'provider')
    ->get();

echo "<pre>API Logs Count by Service and Provider:\n";
echo "========================================\n";
foreach ($logs as $log) {
    echo sprintf("Service: %-15s | Provider: %-15s | Total Logs: %d\n", $log->service, $log->provider, $log->total);
}
echo "========================================\n";

// Show the last 10 logs
$lastLogs = DB::table('api_logs')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

echo "\nLast 10 API Logs:\n";
foreach ($lastLogs as $l) {
    echo sprintf("[%s] ID: %d | Service: %s | Provider: %s | Status: %s | Reference: %s\n", 
        $l->created_at ?? 'N/A', 
        $l->id, 
        $l->service, 
        $l->provider, 
        $l->success ? 'OK' : 'FAIL', 
        $l->reference
    );
}
echo "</pre>";
