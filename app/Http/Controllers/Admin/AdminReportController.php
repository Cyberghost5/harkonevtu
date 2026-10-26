<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SystemReportMail;
use App\Models\AppSetting;
use App\Models\User;
use App\Services\PlatformReportPdfService;
use App\Services\ReportAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AdminReportController extends Controller
{
    /**
     * Display the Reports and Analytics page.
     */
    public function index(Request $request): View
    {
        $preset = $request->input('preset', 'this_week');
        $from   = $request->input('date_from');
        $to     = $request->input('date_to');

        $range = ReportAnalyticsService::resolveDateRange($preset, $from, $to);
        $start = $range['start'];
        $end   = $range['end'];
        $label = $range['label'];

        // Sorting parameters
        $sortService = $request->input('sort_service', 'volume');
        $dirService  = strtolower($request->input('dir_service', 'desc')) === 'asc' ? 'asc' : 'desc';

        $sortGateway = $request->input('sort_gateway', 'volume');
        $dirGateway  = strtolower($request->input('dir_gateway', 'desc')) === 'asc' ? 'asc' : 'desc';

        $sortUsers   = $request->input('sort_users', 'total_spent');
        $dirUsers    = strtolower($request->input('dir_users', 'desc')) === 'asc' ? 'asc' : 'desc';

        $overview    = ReportAnalyticsService::getPlatformOverview($start, $end);
        $services    = ReportAnalyticsService::getServiceBreakdown($start, $end, $sortService, $dirService);
        $gateways    = ReportAnalyticsService::getGatewayBreakdown($start, $end, $sortGateway, $dirGateway);
        $topUsers    = ReportAnalyticsService::getTopUsers($start, $end, 15, $sortUsers, $dirUsers);
        $dailyTrends = ReportAnalyticsService::getDailyTrends($start, $end);

        // Automation Settings
        $schedule = [
            'enabled'    => AppSetting::get('reports_automated_enabled', '0') === '1',
            'frequency'  => AppSetting::get('reports_frequency', 'weekly'),
            'recipients' => AppSetting::get('reports_email_recipients', ''),
            'last_sent'  => AppSetting::get('reports_last_sent_at'),
        ];

        return view('admin.reports.index', compact(
            'overview',
            'services',
            'gateways',
            'topUsers',
            'dailyTrends',
            'range',
            'preset',
            'from',
            'to',
            'sortService',
            'dirService',
            'sortGateway',
            'dirGateway',
            'sortUsers',
            'dirUsers',
            'schedule'
        ));
    }

    /**
     * Export the report as a native PDF file.
     */
    public function exportPdf(Request $request): Response
    {
        $preset = $request->input('preset', 'this_week');
        $from   = $request->input('date_from');
        $to     = $request->input('date_to');

        $range = ReportAnalyticsService::resolveDateRange($preset, $from, $to);
        $start = $range['start'];
        $end   = $range['end'];
        $label = $range['label'];

        $overview = ReportAnalyticsService::getPlatformOverview($start, $end);
        $services = ReportAnalyticsService::getServiceBreakdown($start, $end);
        $gateways = ReportAnalyticsService::getGatewayBreakdown($start, $end);
        $topUsers = ReportAnalyticsService::getTopUsers($start, $end, 15);

        $title = 'Executive System & Financial Analytics Report';
        $pdfService = new PlatformReportPdfService($title, $label);
        $pdfContent = $pdfService->buildReport($overview, $services, $gateways, $topUsers);

        $dateSlug = $start->format('Ymd') . '_' . $end->format('Ymd');
        $filename = "VTU_System_Report_{$preset}_{$dateSlug}.pdf";

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length'      => strlen($pdfContent),
            'Cache-Control'       => 'private, max-age=0, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }

    /**
     * Clean, print-optimized web view.
     */
    public function printView(Request $request): View
    {
        $preset = $request->input('preset', 'this_week');
        $from   = $request->input('date_from');
        $to     = $request->input('date_to');

        $range = ReportAnalyticsService::resolveDateRange($preset, $from, $to);
        $start = $range['start'];
        $end   = $range['end'];
        $label = $range['label'];

        $overview = ReportAnalyticsService::getPlatformOverview($start, $end);
        $services = ReportAnalyticsService::getServiceBreakdown($start, $end);
        $gateways = ReportAnalyticsService::getGatewayBreakdown($start, $end);
        $topUsers = ReportAnalyticsService::getTopUsers($start, $end, 20);

        return view('admin.reports.print', compact('overview', 'services', 'gateways', 'topUsers', 'range'));
    }

    /**
     * Trigger immediate email dispatch of report with PDF attached.
     */
    public function sendEmailReport(Request $request)
    {
        $preset = $request->input('preset', 'this_week');
        $from   = $request->input('date_from');
        $to     = $request->input('date_to');

        $range = ReportAnalyticsService::resolveDateRange($preset, $from, $to);
        $start = $range['start'];
        $end   = $range['end'];
        $label = $range['label'];

        // Recipients
        $recipients = [];
        $adminSettingEmail = AppSetting::get('admin_email');
        if ($adminSettingEmail && filter_var($adminSettingEmail, FILTER_VALIDATE_EMAIL)) {
            $recipients[] = strtolower(trim($adminSettingEmail));
        }

        $adminUserEmails = User::where('is_admin', true)->pluck('email')->toArray();
        foreach ($adminUserEmails as $em) {
            if (filter_var($em, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = strtolower(trim($em));
            }
        }

        $customEmailsRaw = AppSetting::get('reports_email_recipients', '');
        if (!empty($customEmailsRaw)) {
            $extraList = array_map('trim', explode(',', $customEmailsRaw));
            foreach ($extraList as $em) {
                if (!empty($em) && filter_var($em, FILTER_VALIDATE_EMAIL)) {
                    $recipients[] = strtolower($em);
                }
            }
        }

        $recipients = array_unique(array_filter($recipients));

        if (empty($recipients)) {
            return back()->with('error', 'No valid recipient email address configured.');
        }

        $overview = ReportAnalyticsService::getPlatformOverview($start, $end);
        $services = ReportAnalyticsService::getServiceBreakdown($start, $end);
        $gateways = ReportAnalyticsService::getGatewayBreakdown($start, $end);
        $topUsers = ReportAnalyticsService::getTopUsers($start, $end, 15);

        $pdfService = new PlatformReportPdfService('Executive System & Financial Analytics Report', $label);
        $pdfContent = $pdfService->buildReport($overview, $services, $gateways, $topUsers);

        $filename = "VTU_Report_{$preset}_" . date('Ymd_His') . ".pdf";

        try {
            Mail::to($recipients)->send(new SystemReportMail(
                overview: $overview,
                services: array_slice($services, 0, 5),
                periodLabel: $label,
                pdfContent: $pdfContent,
                filename: $filename
            ));

            AppSetting::set('reports_last_sent_at', Carbon::now()->toDateTimeString());

            return back()->with('success', 'Report email with attached PDF has been sent successfully to: ' . implode(', ', $recipients));

        } catch (\Throwable $e) {
            Log::error('Manual report email dispatch failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to send report email: ' . $e->getMessage());
        }
    }

    /**
     * Update automated report schedule configuration.
     */
    public function updateSchedule(Request $request)
    {
        $validated = $request->validate([
            'reports_automated_enabled' => ['required', 'in:0,1'],
            'reports_frequency'         => ['required', 'in:daily,weekly,both'],
            'reports_email_recipients'  => ['nullable', 'string', 'max:500'],
        ]);

        AppSetting::set('reports_automated_enabled', $validated['reports_automated_enabled']);
        AppSetting::set('reports_frequency', $validated['reports_frequency']);
        AppSetting::set('reports_email_recipients', $validated['reports_email_recipients'] ?? '');

        return back()->with('success', 'Automated email report schedule updated successfully.');
    }
}
