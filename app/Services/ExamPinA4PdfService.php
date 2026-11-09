<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\ServiceTransaction;
use App\Services\Fpdf\Fpdf;
use Illuminate\Support\Collection;

class ExamPinA4PdfService extends Fpdf
{
    protected string $siteName;

    // A4 Portrait layout: 210mm x 297mm
    // 2 columns x 5 rows = 10 cards per A4 page
    protected float $marginL     = 10.0; // 10mm left margin
    protected float $marginT     = 10.0; // 10mm top margin
    protected float $cardW       = 92.0; // 92mm width per card
    protected float $cardH       = 48.0; // 48mm height per card
    protected float $gapX        = 6.0;  // 6mm horizontal gap
    protected float $gapY        = 5.0;  // 5mm vertical gap
    protected int   $cols        = 2;    // 2 columns
    protected int   $rowsPerPage = 5;    // 5 rows = 10 cards per page

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
        $this->SetAutoPageBreak(false);
        $this->SetMargins(10, 10, 10);
        $this->AliasNbPages('{nb}');
    }

    /**
     * Extract normalized pins array from a ServiceTransaction or raw array.
     * Each item: ['pin' => string, 'serial' => ?string, 'exam_name' => string, 'exam_type' => string, 'date' => string, 'reference' => string]
     *
     * @param ServiceTransaction|array $tx
     * @return array<int, array{pin: string, serial: ?string, exam_name: string, exam_type: string, date: string, reference: string}>
     */
    public static function extractNormalizedPins($tx): array
    {
        $apiResponse = [];
        $provider    = 'waec';
        $reference   = '';
        $date        = date('d/m/Y');

        if ($tx instanceof ServiceTransaction) {
            $apiResponse = $tx->api_response ?? [];
            $provider    = $tx->provider ?: 'waec';
            $reference   = $tx->reference ?: '';
            $date        = $tx->created_at ? $tx->created_at->format('d/m/Y') : date('d/m/Y');
        } elseif (is_array($tx)) {
            $apiResponse = $tx['api_response'] ?? $tx;
            $provider    = $tx['provider'] ?? ($tx['exam_type'] ?? 'waec');
            $reference   = $tx['reference'] ?? '';
            $date        = !empty($tx['created_at']) ? date('d/m/Y', strtotime($tx['created_at'])) : date('d/m/Y');
        }

        $examName = $apiResponse['exam_name'] ?? strtoupper((string) $provider);
        if ($examName === 'WAEC') $examName = 'WAEC Result Checker';
        if ($examName === 'NECO') $examName = 'NECO Result Token';
        if ($examName === 'NABTEB') $examName = 'NABTEB Result Checker';
        if ($examName === 'JAMB') $examName = 'JAMB / UTME PIN';

        // Candidates for pins
        $candidates = $apiResponse['pins']
            ?? $apiResponse['tokens']
            ?? $apiResponse['cards']
            ?? $apiResponse['data']['cards']
            ?? $apiResponse['data']['tokens']
            ?? $apiResponse['details']['cards']
            ?? [];

        $rawPins = [];
        if (is_array($candidates) && !empty($candidates)) {
            foreach ($candidates as $item) {
                if (is_array($item)) {
                    $pin    = $item['pin'] ?? $item['Pin'] ?? $item['token'] ?? $item['code'] ?? '';
                    $serial = $item['serial'] ?? $item['Serial'] ?? $item['serialnumber'] ?? $item['serial_no'] ?? null;
                    if ($pin !== '') {
                        $rawPins[] = ['pin' => (string) $pin, 'serial' => $serial ? (string) $serial : null];
                    }
                } elseif (is_string($item) && trim($item) !== '') {
                    if (str_contains($item, '<=>')) {
                        [$p, $s] = explode('<=>', $item, 2);
                        $rawPins[] = ['pin' => trim($p), 'serial' => trim($s)];
                    } else {
                        $rawPins[] = ['pin' => trim($item), 'serial' => null];
                    }
                }
            }
        }

        // Check fallback fields (token, pin, token1, etc.)
        if (empty($rawPins)) {
            $pool = is_array($apiResponse['data'] ?? null) ? array_merge($apiResponse, $apiResponse['data']) : $apiResponse;
            foreach ($pool as $k => $v) {
                if (preg_match('/^(pin|token)\d*$/i', (string) $k) && is_string($v) && trim($v) !== '') {
                    if (str_contains($v, '<=>')) {
                        [$p, $s] = explode('<=>', $v, 2);
                        $rawPins[] = ['pin' => trim($p), 'serial' => trim($s)];
                    } else {
                        $rawPins[] = ['pin' => trim($v), 'serial' => null];
                    }
                }
            }
        }

        $result = [];
        foreach ($rawPins as $p) {
            $result[] = [
                'pin'        => (string) $p['pin'],
                'serial'     => $p['serial'] ? (string) $p['serial'] : null,
                'exam_name'  => $examName,
                'exam_type'  => strtolower((string) $provider),
                'date'       => $date,
                'reference'  => $reference,
            ];
        }

        return $result;
    }

    /**
     * Build the 2-column A4 PDF from a collection of Exam PIN cards.
     *
     * @param array<int, array{pin: string, serial: ?string, exam_name: string, exam_type: string, date: string, reference: string}> $cards
     * @return string Raw PDF binary string
     */
    public function generate(array $cards): string
    {
        if (empty($cards)) {
            $this->AddPage();
            $this->SetFont('Helvetica', 'B', 14);
            $this->SetXY(20, 50);
            $this->Cell(170, 10, 'No exam pins found for this selection.', 0, 1, 'C');
            return $this->Output('S');
        }

        $cardsPerPage = $this->cols * $this->rowsPerPage; // 10

        foreach ($cards as $index => $card) {
            $pageIndex = $index % $cardsPerPage;

            if ($pageIndex === 0) {
                $this->AddPage();
            }

            $col = $pageIndex % $this->cols;
            $row = (int) floor($pageIndex / $this->cols);

            $posX = $this->marginL + ($col * ($this->cardW + $this->gapX));
            $posY = $this->marginT + ($row * ($this->cardH + $this->gapY));

            $this->drawExamCard($card, $posX, $posY, $this->cardW, $this->cardH);
        }

        return $this->Output('S');
    }

    /**
     * Draw an individual Exam PIN card slip.
     */
    protected function drawExamCard(array $card, float $x, float $y, float $w, float $h): void
    {
        $examType = strtolower($card['exam_type'] ?? 'waec');
        $examName = (string) ($card['exam_name'] ?? 'Exam Result Checker');
        $pin      = (string) ($card['pin'] ?? '');
        $serial   = (string) ($card['serial'] ?? '');
        $date     = (string) ($card['date'] ?? date('d/m/Y'));
        $ref      = (string) ($card['reference'] ?? '');

        // Format PIN (groups of 4 if numbers)
        $cleanPin = preg_replace('/\s+/', '', $pin);
        $formattedPin = strlen($cleanPin) >= 10 ? trim(chunk_split($cleanPin, 4, ' ')) : $cleanPin;

        $theme = $this->getExamTheme($examType);

        // 1. Outer Border Card
        $this->SetDrawColor(200, 205, 215);
        $this->SetLineWidth(0.3);
        $this->SetFillColor(255, 255, 255);
        $this->Rect($x, $y, $w, $h, 'DF');

        // 2. Header Banner
        $headerH = 9.0;
        $this->SetFillColor($theme['r'], $theme['g'], $theme['b']);
        $this->Rect($x, $y, $w, $headerH, 'F');

        // Header Title
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetXY($x + 2.5, $y + 1.2);
        $this->Cell($w - 20, 4, strtoupper($examName), 0, 0, 'L');

        // Body Badge on Header Right
        $this->SetFont('Helvetica', 'B', 7.5);
        $this->SetXY($x + $w - 18, $y + 1.5);
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor($theme['r'], $theme['g'], $theme['b']);
        $this->Cell(15.5, 4.5, strtoupper($examType), 0, 0, 'C', true);

        // Subheader instructions / Portal
        $portal = $theme['portal'];
        $this->SetTextColor(100, 116, 139);
        $this->SetFont('Helvetica', '', 6.5);
        $this->SetXY($x + 2.5, $y + $headerH + 1.5);
        $this->Cell($w - 5, 3.5, 'Official Portal: ' . $portal, 0, 0, 'L');

        // 3. PIN Box
        $pinBoxY = $y + $headerH + 6.0;
        $pinBoxH = 11.5;
        $this->SetFillColor(245, 247, 250);
        $this->SetDrawColor($theme['r'], $theme['g'], $theme['b']);
        $this->SetLineWidth(0.35);
        $this->Rect($x + 2.5, $pinBoxY, $w - 5, $pinBoxH, 'DF');

        // PIN Label
        $this->SetTextColor(100, 116, 139);
        $this->SetFont('Helvetica', 'B', 6);
        $this->SetXY($x + 4, $pinBoxY + 1.0);
        $this->Cell(20, 3, 'EXAM PIN / TOKEN:', 0, 0, 'L');

        // PIN Value
        $this->SetTextColor(15, 23, 42);
        $this->SetFont('Courier', 'B', 11.5);
        $this->SetXY($x + 4, $pinBoxY + 4.2);
        $this->Cell($w - 8, 6.0, $formattedPin, 0, 0, 'L');

        // 4. Serial Number Box (if available)
        $detailY = $pinBoxY + $pinBoxH + 2.0;
        $this->SetFont('Helvetica', 'B', 6.5);
        $this->SetTextColor(71, 85, 105);
        $this->SetXY($x + 2.5, $detailY);
        $serialText = $serial !== '' ? 'SERIAL: ' . $serial : 'SERIAL: N/A';
        $this->Cell($w - 5, 3.5, $serialText, 0, 0, 'L');

        // Instructions text
        $instrY = $detailY + 4.0;
        $this->SetFont('Helvetica', '', 5.8);
        $this->SetTextColor(100, 116, 139);
        $this->SetXY($x + 2.5, $instrY);
        $this->Cell($w - 5, 3.2, $theme['instruction'], 0, 0, 'L');

        // 5. Card Footer
        $footerY = $y + $h - 6.5;
        $this->SetDrawColor(226, 232, 240);
        $this->SetLineWidth(0.2);
        $this->Line($x + 2.5, $footerY, $x + $w - 2.5, $footerY);

        $this->SetFont('Helvetica', '', 5.5);
        $this->SetTextColor(148, 163, 184);
        $this->SetXY($x + 2.5, $footerY + 1.2);
        $this->Cell(($w - 5) / 2, 3.5, 'Date: ' . $date, 0, 0, 'L');

        $this->SetFont('Helvetica', 'B', 5.5);
        $this->SetTextColor($theme['r'], $theme['g'], $theme['b']);
        $this->SetXY($x + ($w / 2), $footerY + 1.2);
        $this->Cell(($w - 5) / 2, 3.5, $this->siteName, 0, 0, 'R');
    }

    /**
     * Return branding theme & portal info for examination body.
     *
     * @return array{r: int, g: int, b: int, portal: string, instruction: string}
     */
    protected function getExamTheme(string $exam): array
    {
        switch (strtolower($exam)) {
            case 'waec':
                return [
                    'r'           => 30,
                    'g'           => 58,
                    'b'           => 138, // Deep Royal Blue
                    'portal'      => 'www.waecdirect.org',
                    'instruction' => 'Visit portal, click Check Result, enter Card PIN & Serial Number.',
                ];
            case 'neco':
                return [
                    'r'           => 5,
                    'g'           => 150,
                    'b'           => 105, // Emerald Green
                    'portal'      => 'results.neco.gov.ng',
                    'instruction' => 'Visit portal, select Exam Year & Type, enter Token to check result.',
                ];
            case 'nabteb':
                return [
                    'r'           => 217,
                    'g'           => 119,
                    'b'           => 6,   // Amber Orange
                    'portal'      => 'eworld.nabtebnigeria.org',
                    'instruction' => 'Visit portal, enter Candidate No, Exam Year, PIN & Serial.',
                ];
            case 'jamb':
                return [
                    'r'           => 109,
                    'g'           => 40,
                    'b'           => 217, // Indigo Purple
                    'portal'      => 'efacility.jamb.gov.ng',
                    'instruction' => 'Use PIN on official JAMB portal for UTME Candidate profile/services.',
                ];
            default:
                return [
                    'r'           => 79,
                    'g'           => 70,
                    'b'           => 229, // Slate Indigo
                    'portal'      => 'Official Result Checker',
                    'instruction' => 'Enter PIN & Serial number on the official exam board portal.',
                ];
        }
    }
}
