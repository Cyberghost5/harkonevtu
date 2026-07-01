<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AdminMonitoringController extends Controller
{
    /**
     * Render the admin server monitoring page.
     */
    public function index(): View
    {
        $themeColor = AppSetting::get('theme_color', '#4f46e5');
        return view('admin.monitoring', compact('themeColor'));
    }

    /**
     * Fetch real-time system metrics as JSON.
     */
    public function data(): JsonResponse
    {
        $os = PHP_OS_FAMILY;

        if ($os === 'Windows') {
            $cpu = $this->getWindowsCpuUsage();
            $memory = $this->getWindowsMemoryUsage();
            $diskIO = $this->getWindowsDiskIO();
            $network = $this->getWindowsNetworkThroughput();
        } else {
            // Default to Linux/Unix style parsing
            $cpu = $this->getLinuxCpuUsage();
            $memory = $this->getLinuxMemoryUsage();
            $diskIO = $this->getLinuxDiskIO();
            $network = $this->getLinuxNetworkThroughput();
        }

        $diskSpace = $this->getDiskSpace();

        return response()->json([
            'cpu' => $cpu,
            'memory' => $memory,
            'disk_space' => $diskSpace,
            'disk_io' => $diskIO,
            'network' => $network,
            'os' => $os,
        ]);
    }

    /**
     * ─── CPU Monitoring ──────────────────────────────────────────────────────
     */
    private function getWindowsCpuUsage(): float
    {
        $output = shell_exec('wmic cpu get LoadPercentage 2>&1');
        if ($output) {
            $lines = array_filter(array_map('trim', explode("\n", $output)));
            foreach ($lines as $line) {
                if (is_numeric($line)) {
                    return (float)$line;
                }
            }
        }

        $output = shell_exec('powershell -Command "Get-CimInstance Win32_Processor | Select-Object -ExpandProperty LoadPercentage"');
        if ($output && is_numeric(trim($output))) {
            return (float)trim($output);
        }

        return 0.0;
    }

    private function getLinuxCpuUsage(): float
    {
        $stat1 = $this->readLinuxCpuStats();
        if (!$stat1) return 0.0;

        usleep(100000); // 100ms delay

        $stat2 = $this->readLinuxCpuStats();
        if (!$stat2) return 0.0;

        $idle1 = $stat1['idle'] + $stat1['iowait'];
        $nonIdle1 = $stat1['user'] + $stat1['nice'] + $stat1['system'] + $stat1['irq'] + $stat1['softirq'] + $stat1['steal'];
        $total1 = $idle1 + $nonIdle1;

        $idle2 = $stat2['idle'] + $stat2['iowait'];
        $nonIdle2 = $stat2['user'] + $stat2['nice'] + $stat2['system'] + $stat2['irq'] + $stat2['softirq'] + $stat2['steal'];
        $total2 = $idle2 + $nonIdle2;

        $diffTotal = $total2 - $total1;
        $diffIdle = $idle2 - $idle1;

        if ($diffTotal === 0) return 0.0;

        return round((($diffTotal - $diffIdle) / $diffTotal) * 100, 2);
    }

    private function readLinuxCpuStats(): ?array
    {
        if (!is_readable('/proc/stat')) return null;
        $content = file_get_contents('/proc/stat');
        if (!$content) return null;

        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (strpos($line, 'cpu ') === 0) {
                $parts = preg_split('/\s+/', trim($line));
                return [
                    'user' => (int)($parts[1] ?? 0),
                    'nice' => (int)($parts[2] ?? 0),
                    'system' => (int)($parts[3] ?? 0),
                    'idle' => (int)($parts[4] ?? 0),
                    'iowait' => (int)($parts[5] ?? 0),
                    'irq' => (int)($parts[6] ?? 0),
                    'softirq' => (int)($parts[7] ?? 0),
                    'steal' => (int)($parts[8] ?? 0),
                ];
            }
        }
        return null;
    }

    /**
     * ─── Memory Monitoring ───────────────────────────────────────────────────
     */
    private function getWindowsMemoryUsage(): array
    {
        $output = shell_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value 2>&1');
        $total = 0;
        $free = 0;

        if ($output) {
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                if (strpos($line, 'FreePhysicalMemory=') === 0) {
                    $free = (int)trim(substr($line, strlen('FreePhysicalMemory='))) * 1024; // KB to Bytes
                }
                if (strpos($line, 'TotalVisibleMemorySize=') === 0) {
                    $total = (int)trim(substr($line, strlen('TotalVisibleMemorySize='))) * 1024; // KB to Bytes
                }
            }
        }

        if ($total === 0) {
            $output = shell_exec('powershell -Command "Get-CimInstance Win32_OperatingSystem | Select-Object TotalVisibleMemorySize, FreePhysicalMemory | ConvertTo-Json"');
            if ($output) {
                $data = json_decode($output, true);
                if (isset($data['TotalVisibleMemorySize'])) {
                    $total = (int)$data['TotalVisibleMemorySize'] * 1024;
                    $free = (int)$data['FreePhysicalMemory'] * 1024;
                }
            }
        }

        $used = $total - $free;
        $percentage = $total > 0 ? round(($used / $total) * 100, 2) : 0.0;

        // Get Paging File (Swap)
        $swapTotal = 0;
        $swapUsed = 0;
        $pageOutput = shell_exec('wmic pagefile get AllocatedBaseSize,CurrentUsage /Value 2>&1');
        if ($pageOutput) {
            $lines = explode("\n", $pageOutput);
            foreach ($lines as $line) {
                if (strpos($line, 'AllocatedBaseSize=') === 0) {
                    $swapTotal = (int)trim(substr($line, strlen('AllocatedBaseSize='))) * 1024 * 1024; // MB to Bytes
                }
                if (strpos($line, 'CurrentUsage=') === 0) {
                    $swapUsed = (int)trim(substr($line, strlen('CurrentUsage='))) * 1024 * 1024; // MB to Bytes
                }
            }
        }

        $swapPercentage = $swapTotal > 0 ? round(($swapUsed / $swapTotal) * 100, 2) : 0.0;

        return [
            'total' => $total,
            'used' => $used,
            'percentage' => $percentage,
            'swap_total' => $swapTotal,
            'swap_used' => $swapUsed,
            'swap_percentage' => $swapPercentage,
        ];
    }

    private function getLinuxMemoryUsage(): array
    {
        if (!is_readable('/proc/meminfo')) {
            return ['total' => 0, 'used' => 0, 'percentage' => 0.0, 'swap_total' => 0, 'swap_used' => 0, 'swap_percentage' => 0.0];
        }
        $content = file_get_contents('/proc/meminfo');
        $lines = explode("\n", $content);
        $mem = [];
        foreach ($lines as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $matches)) {
                $mem[$matches[1]] = (int)$matches[2] * 1024; // Convert KB to Bytes
            }
        }

        $total = $mem['MemTotal'] ?? 0;
        $free = $mem['MemFree'] ?? 0;
        $buffers = $mem['Buffers'] ?? 0;
        $cached = $mem['Cached'] ?? 0;

        $used = $total - $free - $buffers - $cached;
        $percentage = $total > 0 ? round(($used / $total) * 100, 2) : 0.0;

        $swapTotal = $mem['SwapTotal'] ?? 0;
        $swapFree = $mem['SwapFree'] ?? 0;
        $swapUsed = $swapTotal - $swapFree;
        $swapPercentage = $swapTotal > 0 ? round(($swapUsed / $swapTotal) * 100, 2) : 0.0;

        return [
            'total' => $total,
            'used' => $used,
            'percentage' => $percentage,
            'swap_total' => $swapTotal,
            'swap_used' => $swapUsed,
            'swap_percentage' => $swapPercentage,
        ];
    }

    /**
     * ─── Disk Space ──────────────────────────────────────────────────────────
     */
    private function getDiskSpace(): array
    {
        $path = PHP_OS_FAMILY === 'Windows' ? substr(base_path(), 0, 3) : '/';
        $total = @disk_total_space($path) ?: 0;
        $free = @disk_free_space($path) ?: 0;
        $used = $total - $free;
        $percentage = $total > 0 ? round(($used / $total) * 100, 2) : 0.0;

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'percentage' => $percentage,
        ];
    }

    /**
     * ─── Disk I/O ────────────────────────────────────────────────────────────
     */
    private function getWindowsDiskIO(): array
    {
        $output = shell_exec('wmic path Win32_PerfFormattedData_PerfDisk_PhysicalDisk where Name="_Total" get DiskReadBytesPerSec,DiskWriteBytesPerSec /Value 2>&1');
        $read = 0.0;
        $write = 0.0;

        if ($output) {
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                if (strpos($line, 'DiskReadBytesPerSec=') === 0) {
                    $read = (float)trim(substr($line, strlen('DiskReadBytesPerSec=')));
                }
                if (strpos($line, 'DiskWriteBytesPerSec=') === 0) {
                    $write = (float)trim(substr($line, strlen('DiskWriteBytesPerSec=')));
                }
            }
        }

        return [
            'read_speed' => $read,
            'write_speed' => $write,
        ];
    }

    private function getLinuxDiskIO(): array
    {
        if (!is_readable('/proc/diskstats')) {
            return ['read_speed' => 0.0, 'write_speed' => 0.0];
        }

        $stats1 = $this->readLinuxDiskStats();
        usleep(100000); // 100ms
        $stats2 = $this->readLinuxDiskStats();

        if (!$stats1 || !$stats2) {
            return ['read_speed' => 0.0, 'write_speed' => 0.0];
        }

        $sectorsRead = $stats2['sectors_read'] - $stats1['sectors_read'];
        $sectorsWritten = $stats2['sectors_written'] - $stats1['sectors_written'];

        $readBytes = $sectorsRead * 512 * 10;
        $writeBytes = $sectorsWritten * 512 * 10;

        return [
            'read_speed' => (float)$readBytes,
            'write_speed' => (float)$writeBytes,
        ];
    }

    private function readLinuxDiskStats(): ?array
    {
        $content = @file_get_contents('/proc/diskstats');
        if (!$content) return null;

        $lines = explode("\n", trim($content));
        $totalRead = 0;
        $totalWritten = 0;

        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 10) {
                $totalRead += (int)$parts[5];
                $totalWritten += (int)$parts[9];
            }
        }
        return ['sectors_read' => $totalRead, 'sectors_written' => $totalWritten];
    }

    /**
     * ─── Network Throughput ──────────────────────────────────────────────────
     */
    private function getWindowsNetworkThroughput(): array
    {
        $output = shell_exec('wmic path Win32_PerfFormattedData_Tcpip_NetworkInterface get BytesReceivedPerSec,BytesSentPerSec /Value 2>&1');
        $rx = 0.0;
        $tx = 0.0;

        if ($output) {
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                if (strpos($line, 'BytesReceivedPerSec=') === 0) {
                    $rx += (float)trim(substr($line, strlen('BytesReceivedPerSec=')));
                }
                if (strpos($line, 'BytesSentPerSec=') === 0) {
                    $tx += (float)trim(substr($line, strlen('BytesSentPerSec=')));
                }
            }
        }

        return [
            'incoming' => $rx,
            'outgoing' => $tx,
        ];
    }

    private function getLinuxNetworkThroughput(): array
    {
        if (!is_readable('/proc/net/dev')) {
            return ['incoming' => 0.0, 'outgoing' => 0.0];
        }

        $net1 = $this->readLinuxNetworkStats();
        usleep(100000); // 100ms
        $net2 = $this->readLinuxNetworkStats();

        if (!$net1 || !$net2) {
            return ['incoming' => 0.0, 'outgoing' => 0.0];
        }

        $rxBytes = $net2['rx'] - $net1['rx'];
        $txBytes = $net2['tx'] - $net1['tx'];

        return [
            'incoming' => (float)($rxBytes * 10),
            'outgoing' => (float)($txBytes * 10),
        ];
    }

    private function readLinuxNetworkStats(): ?array
    {
        $content = @file_get_contents('/proc/net/dev');
        if (!$content) return null;

        $lines = explode("\n", trim($content));
        $rx = 0;
        $tx = 0;
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                $parts = preg_split('/\s+/', trim(substr($line, strpos($line, ':') + 1)));
                if (count($parts) >= 9) {
                    $rx += (int)$parts[0];
                    $tx += (int)$parts[8];
                }
            }
        }
        return ['rx' => $rx, 'tx' => $tx];
    }
}
