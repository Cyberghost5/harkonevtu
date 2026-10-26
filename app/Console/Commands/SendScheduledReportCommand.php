<?php

namespace App\Console\Commands;

use App\Mail\SystemReportMail;
use App\Models\AppSetting;
use App\Models\User;
use App\Services\PlatformReportPdfService;
use App\Services\ReportAnalyticsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendScheduledReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:send-scheduled 
                            {frequency=weekly : The report frequency (daily or weekly)} 
                            {--force : Force sending report even if automation setting is disabled}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and email the automated daily or weekly executive platform report with PDF attachment';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $frequency = strtolower($this->argument('frequency'));
        $force     = $this->option('force');

        $this->info("Checking scheduled {$frequency} report delivery...");

        // Check if automation is enabled
        $isEnabled = AppSetting::get('reports_automated_enabled', '0') === '1';
        $configuredFreq = AppSetting::get('reports_frequency', 'weekly');

        if (!$force && !$isEnabled) {
            $this->comment("Automated reports are disabled in Admin Settings. Skipping (use --force to override).");
            return 0;
        }

        if (!$force && $configuredFreq !== 'both' && $configuredFreq !== $frequency) {
            $this->comment("Current schedule is set to '{$configuredFreq}'. Skipping '{$frequency}' dispatch.");
            return 0;
        }

        // Collect recipient emails
        $recipients = [];

        // 1. System admin email
        $adminSettingEmail = AppSetting::get('admin_email');
        if ($adminSettingEmail && filter_var($adminSettingEmail, FILTER_VALIDATE_EMAIL)) {
            $recipients[] = strtolower(trim($adminSettingEmail));
        }

        // 2. Admin users
        $adminUserEmails = User::where('is_admin', true)->pluck('email')->toArray();
        foreach ($adminUserEmails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = strtolower(trim($email));
            }
        }

        // 3. Additional configured emails
        $customEmailsRaw = AppSetting::get('reports_email_recipients', '');
        if (!empty($customEmailsRaw)) {
            $extraList = array_map('trim', explode(',', $customEmailsRaw));
            foreach ($extraList as $email) {
                if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $recipients[] = strtolower($email);
                }
            }
        }

        $recipients = array_unique(array_filter($recipients));

        if (empty($recipients)) {
            $this->warn("No valid recipient emails configured. Aborting report email.");
            return 1;
        }

        $this->info("Recipients: " . implode(', ', $recipients));

        // Resolve date period
        $preset = ($frequency === 'daily') ? 'yesterday' : 'last_week';
        $dateConfig = ReportAnalyticsService::resolveDateRange($preset);
        $start = $dateConfig['start'];
        $end   = $dateConfig['end'];
        $periodLabel = $dateConfig['label'];

        $this->info("Computing platform analytics for: {$periodLabel}...");

        $overview = ReportAnalyticsService::getPlatformOverview($start, $end);
        $services = ReportAnalyticsService::getServiceBreakdown($start, $end);
        $gateways = ReportAnalyticsService::getGatewayBreakdown($start, $end);
        $topUsers = ReportAnalyticsService::getTopUsers($start, $end, 15);

        $this->info("Rendering PDF audit report...");

        $title = ($frequency === 'daily') ? 'Daily System & Financial Audit Report' : 'Weekly Executive Analytics & Financial Report';
        $pdfService = new PlatformReportPdfService($title, $periodLabel);
        $pdfContent = $pdfService->buildReport($overview, $services, $gateways, $topUsers);

        $dateSlug = $start->format('Y-m-d');
        $filename = "VTU_Report_{$frequency}_{$dateSlug}.pdf";

        $this->info("Dispatching email with attached PDF ({$filename})...");

        try {
            Mail::to($recipients)->send(new SystemReportMail(
                overview: $overview,
                services: array_slice($services, 0, 5),
                periodLabel: $periodLabel,
                pdfContent: $pdfContent,
                filename: $filename
            ));

            AppSetting::set('reports_last_sent_at', Carbon::now()->toDateTimeString());

            $this->info("Report successfully dispatched to " . count($recipients) . " recipient(s)!");
            Log::info("Scheduled {$frequency} report dispatched to: " . implode(', ', $recipients));

            return 0;

        } catch (\Throwable $e) {
            $this->error("Failed to send scheduled report email: " . $e->getMessage());
            Log::error("Scheduled report email delivery failed", ['error' => $e->getMessage()]);
            return 1;
        }
    }
}
