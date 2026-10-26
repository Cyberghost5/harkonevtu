<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Services\Fpdf\Fpdf;
use Carbon\Carbon;

class PlatformReportPdfService extends Fpdf
{
    protected string $siteName;
    protected string $reportTitle;
    protected string $periodLabel;
    protected string $generatedAt;

    public function __construct(string $reportTitle = 'Executive System & Financial Analytics Report', string $periodLabel = '')
    {
        parent::__construct('P', 'mm', 'A4');
        $this->siteName    = AppSetting::get('site_name', 'PayPulse VTU');
        $this->reportTitle = $reportTitle;
        $this->periodLabel = $periodLabel;
        $this->generatedAt = Carbon::now()->format('d M Y, h:i A');
        $this->AliasNbPages('{nb}');
        $this->SetAutoPageBreak(true, 18);
        $this->SetMargins(14, 14, 14);
    }

    public function Header(): void
    {
        // Dark branding header bar
        $this->SetFillColor(15, 23, 42); // slate-900
        $this->Rect(0, 0, 210, 28, 'F');

        // Brand accent line
        $this->SetFillColor(16, 185, 129); // emerald-500
        $this->Rect(0, 28, 210, 1.5, 'F');

        // Platform Brand Text
        $this->SetXY(14, 7);
        $this->SetFont('Helvetica', 'B', 15);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(100, 7, strtoupper($this->siteName) . ' - ADMIN REPORT', 0, 0, 'L');

        // Generated timestamp right
        $this->SetXY(114, 7);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(148, 163, 184); // slate-400
        $this->Cell(82, 4, 'Generated: ' . $this->generatedAt, 0, 1, 'R');

        // Period Badge
        $this->SetXY(114, 13);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetTextColor(16, 185, 129); // emerald-400
        $this->Cell(82, 4, $this->periodLabel, 0, 1, 'R');

        // Report Subtitle
        $this->SetXY(14, 15);
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(203, 213, 225); // slate-300
        $this->Cell(100, 4, $this->reportTitle, 0, 1, 'L');

        $this->SetY(34);
    }

    public function Footer(): void
    {
        $this->SetY(-13);
        $this->SetDrawColor(226, 232, 240);
        $this->Line(14, $this->GetY(), 196, $this->GetY());

        $this->SetY(-10);
        $this->SetFont('Helvetica', '', 7.5);
        $this->SetTextColor(148, 163, 184);
        $this->Cell(110, 5, 'CONFIDENTIAL — ' . strtoupper($this->siteName) . ' SYSTEM AUDIT REPORT', 0, 0, 'L');
        $this->Cell(72, 5, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'R');
    }

    /**
     * Generate the complete multi-section report document.
     */
    public function buildReport(array $overview, array $services, array $gateways, array $topUsers): string
    {
        $this->AddPage();

        // ── SECTION 1: EXECUTIVE KPI SUMMARY ──────────────────────────────────
        $this->renderSectionHeader('1. EXECUTIVE FINANCIAL & ACTIVITY SUMMARY');

        $cardW = 58;
        $cardH = 20;
        $startX = 14;
        $startY = $this->GetY() + 2;

        // Row 1
        $this->renderKpiCard($startX, $startY, $cardW, $cardH, 'TOTAL INFLOW (FUNDED)', 'NGN ' . number_format($overview['total_inflow'], 2), $overview['total_inflow_count'] . ' Deposits (Avg: NGN ' . number_format($overview['avg_deposit'], 0) . ')', [16, 185, 129]);
        $this->renderKpiCard($startX + $cardW + 4, $startY, $cardW, $cardH, 'TOTAL OUTFLOW (BILLS)', 'NGN ' . number_format($overview['total_outflow'], 2), $overview['success_count'] . ' Successful (' . $overview['success_rate'] . '%)', [99, 102, 241]);
        $this->renderKpiCard($startX + ($cardW + 4) * 2, $startY, $cardW, $cardH, 'EST. GROSS PROFIT', 'NGN ' . number_format($overview['estimated_profit'], 2), 'Discounts + Fees + A2C Margin', [245, 158, 11]);

        // Row 2
        $startY += $cardH + 4;
        $this->renderKpiCard($startX, $startY, $cardW, $cardH, 'ACTIVE USERS IN PERIOD', number_format($overview['active_users']), $overview['active_user_pct'] . '% of total userbase', [14, 165, 233]);
        $this->renderKpiCard($startX + $cardW + 4, $startY, $cardW, $cardH, 'TOTAL REGISTERED USERS', number_format($overview['total_users']), '+' . $overview['new_signups'] . ' New Registrations', [100, 116, 139]);
        $this->renderKpiCard($startX + ($cardW + 4) * 2, $startY, $cardW, $cardH, 'NET PLATFORM LIQUIDITY', 'NGN ' . number_format($overview['net_flow'], 2), $overview['net_flow'] >= 0 ? 'Positive Capital Inflow' : 'Net Wallet Drawdown', [16, 185, 129]);

        $this->SetY($startY + $cardH + 8);

        // ── SECTION 2: SERVICE BREAKDOWN TABLE ────────────────────────────────
        $this->renderSectionHeader('2. VTU SERVICE SALES & REVENUE PERFORMANCE');

        $headers = [
            ['title' => 'Service Name',          'w' => 54, 'align' => 'L'],
            ['title' => 'Tx Count',              'w' => 24, 'align' => 'C'],
            ['title' => 'Volume (NGN)',          'w' => 40, 'align' => 'R'],
            ['title' => 'Success Rate',          'w' => 28, 'align' => 'C'],
            ['title' => 'Est. Margin (NGN)',     'w' => 36, 'align' => 'R'],
        ];

        $this->renderTableHeader($headers);

        $totVol = 0;
        $totCnt = 0;
        $totMargin = 0;

        foreach ($services as $i => $row) {
            $fill = ($i % 2 === 1);
            $this->SetFillColor(248, 250, 252); // slate-50
            $this->SetTextColor(30, 41, 59);
            $this->SetFont('Helvetica', '', 8.5);

            $this->Cell(54, 6.5, ' ' . $row['name'], 1, 0, 'L', $fill);
            $this->Cell(24, 6.5, number_format($row['count']), 1, 0, 'C', $fill);
            $this->Cell(40, 6.5, number_format($row['volume'], 2) . ' ', 1, 0, 'R', $fill);
            $this->Cell(28, 6.5, $row['success_rate'] . '%', 1, 0, 'C', $fill);
            $this->Cell(36, 6.5, number_format($row['margin'], 2) . ' ', 1, 1, 'R', $fill);

            $totVol += $row['volume'];
            $totCnt += $row['count'];
            $totMargin += $row['margin'];
        }

        // Totals row
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetFillColor(241, 245, 249);
        $this->Cell(54, 7, ' TOTAL SERVICE CONSUMPTION', 1, 0, 'L', true);
        $this->Cell(24, 7, number_format($totCnt), 1, 0, 'C', true);
        $this->Cell(40, 7, number_format($totVol, 2) . ' ', 1, 0, 'R', true);
        $this->Cell(28, 7, $overview['success_rate'] . '%', 1, 0, 'C', true);
        $this->Cell(36, 7, number_format($totMargin, 2) . ' ', 1, 1, 'R', true);

        $this->Ln(6);

        // ── SECTION 3: FUNDING CHANNELS / PAYMENT GATEWAYS ────────────────────
        $this->renderSectionHeader('3. WALLET FUNDING & PAYMENT GATEWAY INFLOW');

        $gwHeaders = [
            ['title' => 'Gateway / Channel',     'w' => 64, 'align' => 'L'],
            ['title' => 'Tx Count',              'w' => 26, 'align' => 'C'],
            ['title' => 'Amount Funded (NGN)',   'w' => 46, 'align' => 'R'],
            ['title' => 'Fee Collected (NGN)',   'w' => 46, 'align' => 'R'],
        ];

        $this->renderTableHeader($gwHeaders);

        $gwTotVol = 0;
        $gwTotCnt = 0;
        $gwTotFees = 0;

        foreach ($gateways as $i => $gw) {
            $fill = ($i % 2 === 1);
            $this->SetFillColor(248, 250, 252);
            $this->SetTextColor(30, 41, 59);
            $this->SetFont('Helvetica', '', 8.5);

            $this->Cell(64, 6.5, ' ' . $gw['name'], 1, 0, 'L', $fill);
            $this->Cell(26, 6.5, number_format($gw['count']), 1, 0, 'C', $fill);
            $this->Cell(46, 6.5, number_format($gw['volume'], 2) . ' ', 1, 0, 'R', $fill);
            $this->Cell(46, 6.5, number_format($gw['fees'], 2) . ' ', 1, 1, 'R', $fill);

            $gwTotVol += $gw['volume'];
            $gwTotCnt += $gw['count'];
            $gwTotFees += $gw['fees'];
        }

        // Gateway Totals
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetFillColor(241, 245, 249);
        $this->Cell(64, 7, ' TOTAL INFLOW RECEIVED', 1, 0, 'L', true);
        $this->Cell(26, 7, number_format($gwTotCnt), 1, 0, 'C', true);
        $this->Cell(46, 7, number_format($gwTotVol, 2) . ' ', 1, 0, 'R', true);
        $this->Cell(46, 7, number_format($gwTotFees, 2) . ' ', 1, 1, 'R', true);

        // ── PAGE BREAK FOR DETAILED USERS & AUDIT ─────────────────────────────
        if (!empty($topUsers)) {
            $this->AddPage();
            $this->renderSectionHeader('4. TOP TRANSACTING USERS (PERIOD LEADERBOARD)');

            $userHeaders = [
                ['title' => 'User Name & Contact', 'w' => 64, 'align' => 'L'],
                ['title' => 'Type',                'w' => 20, 'align' => 'C'],
                ['title' => 'Tx Count',            'w' => 24, 'align' => 'C'],
                ['title' => 'Total Spent (NGN)',   'w' => 40, 'align' => 'R'],
                ['title' => 'Wallet Balance',      'w' => 34, 'align' => 'R'],
            ];

            $this->renderTableHeader($userHeaders);

            foreach ($topUsers as $i => $u) {
                $fill = ($i % 2 === 1);
                $this->SetFillColor(248, 250, 252);
                $this->SetTextColor(30, 41, 59);
                $this->SetFont('Helvetica', '', 8);

                $displayName = substr($u['name'] . ' (' . $u['phone'] . ')', 0, 36);
                $this->Cell(64, 6.5, ' ' . $displayName, 1, 0, 'L', $fill);
                $this->Cell(20, 6.5, ucfirst($u['user_type']), 1, 0, 'C', $fill);
                $this->Cell(24, 6.5, number_format($u['tx_count']), 1, 0, 'C', $fill);
                $this->Cell(40, 6.5, number_format($u['total_spent'], 2) . ' ', 1, 0, 'R', $fill);
                $this->Cell(34, 6.5, number_format($u['balance'], 2) . ' ', 1, 1, 'R', $fill);
            }

            $this->Ln(6);
        }

        // ── SECTION 5: PLATFORM AUDIT & SUMMARY NOTES ─────────────────────────
        $this->renderSectionHeader('5. SYSTEM AUDIT & PLATFORM HEALTH REMARKS');
        $this->SetFillColor(248, 250, 252);
        $this->SetDrawColor(226, 232, 240);
        $this->Rect(14, $this->GetY(), 182, 32, 'DF');

        $this->SetXY(18, $this->GetY() + 3);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(30, 41, 59);
        $this->Cell(174, 5, 'System Integrity & Health Overview:', 0, 1);

        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(71, 85, 105);
        $notes = [
            '• Overall Transaction Success Rate stands at ' . $overview['success_rate'] . '% for the period.',
            '• Platform Userbase: ' . number_format($overview['total_users']) . ' total accounts (' . number_format($overview['agents_count']) . ' agents, ' . number_format($overview['normal_users_count']) . ' regular users).',
            '• Active Engagement: ' . number_format($overview['active_users']) . ' distinct users transacted or logged in during this period.',
            '• Net Platform Liquidity Delta: NGN ' . number_format($overview['net_flow'], 2) . ' (' . ($overview['net_flow'] >= 0 ? 'Surplus Inflow' : 'Deficit / Consumption') . ').',
        ];

        foreach ($notes as $line) {
            $this->SetX(18);
            $this->Cell(174, 4.5, $line, 0, 1);
        }

        return $this->Output('S');
    }

    protected function renderSectionHeader(string $title): void
    {
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetTextColor(15, 23, 42); // slate-900
        $this->Cell(182, 6, $title, 0, 1, 'L');
        $this->SetDrawColor(16, 185, 129); // emerald-500
        $this->SetLineWidth(0.4);
        $this->Line(14, $this->GetY(), 196, $this->GetY());
        $this->Ln(2);
    }

    protected function renderTableHeader(array $headers): void
    {
        $this->SetFillColor(30, 41, 59); // slate-800
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetDrawColor(51, 65, 85);

        foreach ($headers as $col) {
            $this->Cell($col['w'], 6.5, ' ' . $col['title'] . ' ', 1, 0, $col['align'], true);
        }
        $this->Ln();
        $this->SetDrawColor(226, 232, 240);
    }

    protected function renderKpiCard(float $x, float $y, float $w, float $h, string $title, string $value, string $subtext, array $accentRgb): void
    {
        // Card background
        $this->SetFillColor(255, 255, 255);
        $this->SetDrawColor(226, 232, 240); // slate-200
        $this->Rect($x, $y, $w, $h, 'DF');

        // Left accent bar
        $this->SetFillColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
        $this->Rect($x, $y, 1.8, $h, 'F');

        // Card Title
        $this->SetXY($x + 3.5, $y + 2);
        $this->SetFont('Helvetica', 'B', 6.5);
        $this->SetTextColor(100, 116, 139); // slate-500
        $this->Cell($w - 4, 3.5, $title, 0, 1, 'L');

        // Card Value
        $this->SetXY($x + 3.5, $y + 6);
        $this->SetFont('Helvetica', 'B', 10.5);
        $this->SetTextColor(15, 23, 42); // slate-900
        $this->Cell($w - 4, 6, $value, 0, 1, 'L');

        // Card Subtext
        $this->SetXY($x + 3.5, $y + 12.5);
        $this->SetFont('Helvetica', '', 6.5);
        $this->SetTextColor(100, 116, 139);
        $this->Cell($w - 4, 4, $subtext, 0, 1, 'L');
    }
}
