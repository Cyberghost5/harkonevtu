<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\PrintedVoucher;
use App\Services\Fpdf\Fpdf;
use Illuminate\Support\Collection;

class VoucherA4PdfService extends Fpdf
{
    protected string $siteName;

    // A4 layout parameters (5 columns)
    protected float $marginL = 5.0;  // 5mm left margin
    protected float $marginT = 6.0;  // 6mm top margin
    protected float $cardW   = 38.4; // 38.4mm width per card
    protected float $cardH   = 33.0; // 33.0mm height per card
    protected float $gapX    = 2.0;  // 2.0mm horizontal gap between cards
    protected float $gapY    = 2.0;  // 2.0mm vertical gap between cards
    protected int   $cols    = 5;    // Exactly 5 columns across
    protected int   $rowsPerPage = 8; // 8 rows * 5 cols = 40 cards per A4 page

    public function __construct(?string $siteName = null)
    {
        parent::__construct('P', 'mm', 'A4');
        if ($siteName !== null) {
            $this->siteName = $siteName;
        } else {
            try {
                $this->siteName = class_exists(AppSetting::class) ? AppSetting::get('site_name', 'PayPulse VTU') : 'PayPulse VTU';
            } catch (\Throwable $e) {
                $this->siteName = 'PayPulse VTU';
            }
        }
        $this->SetAutoPageBreak(false); // We manage card placement and page breaks manually
        $this->SetMargins(5, 5, 5);
        $this->AliasNbPages('{nb}');
    }

    /**
     * Build the 5-column A4 PDF from a collection of PrintedVouchers.
     *
     * @param Collection<int, PrintedVoucher> $vouchers
     * @return string Raw PDF binary string
     */
    public function generate(Collection $vouchers): string
    {
        if ($vouchers->isEmpty()) {
            $this->AddPage();
            $this->SetFont('Helvetica', 'B', 14);
            $this->SetXY(20, 40);
            $this->Cell(170, 10, 'No vouchers found for this selection.', 0, 1, 'C');
            return $this->Output('S');
        }

        $cardsPerPage = $this->cols * $this->rowsPerPage; // 40
        $totalVouchers = $vouchers->count();

        foreach ($vouchers->values() as $index => $voucher) {
            $pageIndex = $index % $cardsPerPage;

            // Start a new page every 40 vouchers (or on first voucher)
            if ($pageIndex === 0) {
                $this->AddPage();
            }

            $col = $pageIndex % $this->cols;
            $row = (int) floor($pageIndex / $this->cols);

            $posX = $this->marginL + ($col * ($this->cardW + $this->gapX));
            $posY = $this->marginT + ($row * ($this->cardH + $this->gapY));

            $this->drawVoucherCard($voucher, $posX, $posY, $this->cardW, $this->cardH);
        }

        return $this->Output('S');
    }

    /**
     * Draw an individual voucher card slip inside a 5-column grid cell.
     */
    protected function drawVoucherCard(PrintedVoucher $voucher, float $x, float $y, float $w, float $h): void
    {
        $network = strtolower($voucher->network ?? 'mtn');
        $value   = number_format((float) ($voucher->value ?? 0));
        $type    = ucfirst($voucher->type ?? 'airtime');
        $pin     = (string) ($voucher->pin ?? '');
        $serial  = (string) ($voucher->serial_number ?? '');
        $shopName = trim((string) ($voucher->name_on_card ?? $this->siteName));
        $dateStr = $voucher->created_at ? $voucher->created_at->format('d/m/y') : date('d/m/y');

        // Format PIN with spaces every 4 digits for readability (e.g. 1234 5678 9012 3456)
        $cleanPin = preg_replace('/\s+/', '', $pin);
        $formattedPin = trim(chunk_split($cleanPin, 4, ' '));

        // Network styling colors
        $theme = $this->getNetworkTheme($network);

        // 1. Outer Border Box with light dashed/solid cutline
        $this->SetDrawColor(180, 185, 195);
        $this->SetLineWidth(0.2);
        $this->Rect($x, $y, $w, $h, 'D');

        // 2. Network Header Banner
        $headerH = 5.2;
        $this->SetFillColor($theme['bg'][0], $theme['bg'][1], $theme['bg'][2]);
        $this->Rect($x, $y, $w, $headerH, 'F');

        // Header Text: Left = Network + Type, Right = Denomination (N500)
        $this->SetTextColor($theme['text'][0], $theme['text'][1], $theme['text'][2]);
        $this->SetFont('Helvetica', 'B', 6.2);
        $this->SetXY($x + 1.2, $y + 0.8);
        $this->Cell(20, 3.6, strtoupper($network) . ' ' . strtoupper($type), 0, 0, 'L');

        $this->SetFont('Helvetica', 'B', 7.5);
        $this->SetXY($x + $w - 16, $y + 0.8);
        $this->Cell(14.8, 3.6, 'N' . $value, 0, 0, 'R');

        // 3. PIN Box (Light shaded background with clear monospaced PIN)
        $pinBoxY = $y + $headerH + 1.2;
        $pinBoxH = 10.0;
        $this->SetFillColor(243, 244, 246); // slate-100
        $this->SetDrawColor(220, 225, 230);
        $this->Rect($x + 1.0, $pinBoxY, $w - 2.0, $pinBoxH, 'DF');

        // PIN Label
        $this->SetTextColor(100, 116, 139); // slate-500
        $this->SetFont('Helvetica', 'B', 4.5);
        $this->SetXY($x + 1.0, $pinBoxY + 0.8);
        $this->Cell($w - 2.0, 2.5, 'RECHARGE PIN', 0, 1, 'C');

        // Formatted PIN digits
        $this->SetTextColor(15, 23, 42); // slate-900 (bold black)
        $pinFontSize = (strlen($cleanPin) > 16) ? 7.0 : 8.2;
        $this->SetFont('Courier', 'B', $pinFontSize);
        $this->SetXY($x + 1.0, $pinBoxY + 3.8);
        $this->Cell($w - 2.0, 4.5, $formattedPin, 0, 1, 'C');

        // 4. Serial Number & Date
        $metaY = $pinBoxY + $pinBoxH + 1.0;
        $this->SetTextColor(71, 85, 105); // slate-600
        $this->SetFont('Helvetica', 'B', 4.8);
        $this->SetXY($x + 1.2, $metaY);
        $this->Cell(22, 2.8, 'S/N: ' . $serial, 0, 0, 'L');

        $this->SetFont('Helvetica', '', 4.6);
        $this->SetXY($x + $w - 14.5, $metaY);
        $this->Cell(13.3, 2.8, $dateStr, 0, 0, 'R');

        // 5. Dial / Recharge Instruction
        $dialCode = $this->getDialCode($network);
        $instY = $metaY + 3.2;
        $this->SetTextColor(30, 41, 59); // slate-800
        $this->SetFont('Helvetica', 'B', 4.8);
        $this->SetXY($x + 1.2, $instY);
        $this->Cell($w - 2.4, 3.2, 'Dial: ' . $dialCode, 0, 1, 'C');

        // 6. Footer (Merch / Powered by)
        $footerY = $instY + 3.5;
        if (!empty($shopName)) {
            $this->SetTextColor(100, 116, 139);
            $this->SetFont('Helvetica', '', 4.5);
            $this->SetXY($x + 1.2, $footerY);
            // Truncate if shopName is very long
            $cleanShop = (strlen($shopName) > 24) ? substr($shopName, 0, 22) . '..' : $shopName;
            $this->Cell($w - 2.4, 2.8, $cleanShop, 0, 1, 'C');
        }
    }

    /**
     * Resolve network theme background and text colors.
     */
    protected function getNetworkTheme(string $network): array
    {
        return match ($network) {
            'mtn' => [
                'bg'   => [250, 204, 21],  // Yellow
                'text' => [113, 63, 18],   // Dark Amber/Brown
            ],
            'airtel' => [
                'bg'   => [239, 68, 68],   // Red
                'text' => [255, 255, 255], // White
            ],
            'glo' => [
                'bg'   => [16, 185, 129],  // Emerald Green
                'text' => [255, 255, 255], // White
            ],
            '9mobile', 'etisalat' => [
                'bg'   => [15, 118, 110],  // Dark Teal/Green
                'text' => [255, 255, 255], // White
            ],
            default => [
                'bg'   => [79, 70, 229],   // Indigo
                'text' => [255, 255, 255], // White
            ],
        };
    }

    /**
     * Get official recharge USSD dial string.
     */
    protected function getDialCode(string $network): string
    {
        return match ($network) {
            'mtn'      => '*311*PIN# (*555*)',
            'airtel'   => '*311*PIN# (*126*)',
            'glo'      => '*311*PIN# (*123*)',
            '9mobile', 'etisalat' => '*311*PIN# (*222*)',
            default    => '*311*PIN#',
        };
    }
}
