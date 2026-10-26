<?php

namespace App\Services\Fpdf;

/**
 * FPDF 1.86 - Pure PHP PDF generation class with PHP 8.x compatibility.
 */
#[\AllowDynamicProperties]
class Fpdf
{
    protected int $page = 0;
    protected int $n = 2;
    protected array $offsets = [];
    protected string $buffer = '';
    protected array $pages = [];
    protected int $state = 0;
    protected bool $compress = true;
    protected float $k;
    protected string $DefOrientation;
    protected string $CurOrientation;
    protected array $StdPageSizes;
    protected array $DefPageSize;
    protected array $CurPageSize;
    protected array $CurRotation = [];
    protected array $PageInfo = [];
    protected float $wPt;
    protected float $hPt;
    protected float $w;
    protected float $h;
    protected float $lMargin;
    protected float $tMargin;
    protected float $rMargin;
    protected float $bMargin;
    protected float $cMargin;
    protected float $x;
    protected float $y;
    protected float $lasth = 0;
    protected float $LineWidth;
    protected string $fontpath = '';
    protected array $CoreFonts = [];
    protected array $fonts = [];
    protected array $FontFiles = [];
    protected array $encodings = [];
    protected array $cmaps = [];
    protected string $FontFamily = '';
    protected string $FontStyle = '';
    protected bool $underline = false;
    protected array $CurrentFont = [];
    protected float $FontSizePt = 12;
    protected float $FontSize = 12;
    protected string $DrawColor = '0 G';
    protected string $FillColor = '0 g';
    protected string $TextColor = '0 g';
    protected bool $ColorFlag = false;
    protected bool $WithAlpha = false;
    protected float $ws = 0;
    protected array $images = [];
    protected array $PageLinks = [];
    protected array $links = [];
    protected bool $AutoPageBreak = true;
    protected float $PageBreakTrigger;
    protected bool $InHeader = false;
    protected bool $InFooter = false;
    protected array $AliasNbPages = [];
    protected string $ZoomMode = 'fullwidth';
    protected string $LayoutMode = 'continuous';
    protected array $metadata = [];
    protected string $PDFVersion = '1.4';

    public function __construct(string $orientation = 'P', string $unit = 'mm', string $size = 'A4')
    {
        $this->CoreFonts = [
            'courier', 'courierB', 'courierI', 'courierBI',
            'helvetica', 'helveticaB', 'helveticaI', 'helveticaBI',
            'times', 'timesB', 'timesI', 'timesBI',
            'symbol', 'zapfdingbats'
        ];

        // Unit scale factor
        $this->k = match ($unit) {
            'pt' => 1.0,
            'mm' => 72.0 / 25.4,
            'cm' => 72.0 / 2.54,
            'in' => 72.0,
            default => 72.0 / 25.4,
        };

        // Standard page sizes
        $this->StdPageSizes = [
            'a3' => [841.89, 1190.55],
            'a4' => [595.28, 841.89],
            'a5' => [420.94, 595.28],
            'letter' => [612.0, 792.0],
            'legal' => [612.0, 1008.0],
        ];

        $sz = strtolower($size);
        $sizeArray = $this->StdPageSizes[$sz] ?? $this->StdPageSizes['a4'];
        $this->DefPageSize = $sizeArray;
        $this->CurPageSize = $sizeArray;

        // Orientation
        $o = strtoupper($orientation);
        if ($o === 'P' || $o === 'PORTRAIT') {
            $this->DefOrientation = 'P';
            $this->w = $sizeArray[0] / $this->k;
            $this->h = $sizeArray[1] / $this->k;
        } else {
            $this->DefOrientation = 'L';
            $this->w = $sizeArray[1] / $this->k;
            $this->h = $sizeArray[0] / $this->k;
        }
        $this->CurOrientation = $this->DefOrientation;
        $this->wPt = $this->w * $this->k;
        $this->hPt = $this->h * $this->k;

        // Page margins (10 mm default)
        $margin = 28.35 / $this->k;
        $this->SetMargins($margin, $margin);
        $this->cMargin = $margin / 10.0;
        $this->LineWidth = 0.567 / $this->k;
        $this->SetAutoPageBreak(true, 2.0 * $margin);
        $this->SetDisplayMode('default');
        $this->compress = function_exists('gzcompress');
    }

    public function SetMargins(float $left, float $top, ?float $right = null): void
    {
        $this->lMargin = $left;
        $this->tMargin = $top;
        $this->rMargin = $right ?? $left;
    }

    public function SetLeftMargin(float $margin): void
    {
        $this->lMargin = $margin;
        if ($this->page > 0 && $this->x < $margin) {
            $this->x = $margin;
        }
    }

    public function SetTopMargin(float $margin): void
    {
        $this->tMargin = $margin;
    }

    public function SetRightMargin(float $margin): void
    {
        $this->rMargin = $margin;
    }

    public function SetAutoPageBreak(bool $auto, float $margin = 0): void
    {
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->PageBreakTrigger = $this->h - $margin;
    }

    public function SetDisplayMode(string $zoom, string $layout = 'continuous'): void
    {
        $this->ZoomMode = $zoom;
        $this->LayoutMode = $layout;
    }

    public function SetCompression(bool $compress): void
    {
        $this->compress = $compress && function_exists('gzcompress');
    }

    public function SetTitle(string $title, bool $isUTF8 = false): void
    {
        $this->metadata['Title'] = $isUTF8 ? $title : utf8_encode($title);
    }

    public function SetAuthor(string $author, bool $isUTF8 = false): void
    {
        $this->metadata['Author'] = $isUTF8 ? $author : utf8_encode($author);
    }

    public function SetSubject(string $subject, bool $isUTF8 = false): void
    {
        $this->metadata['Subject'] = $isUTF8 ? $subject : utf8_encode($subject);
    }

    public function AliasNbPages(string $alias = '{nb}'): void
    {
        $this->AliasNbPages = [$alias];
    }

    public function PageNo(): int
    {
        return $this->page;
    }

    public function SetDrawColor(int $r, ?int $g = null, ?int $b = null): void
    {
        if (($r == 0 && $g == 0 && $b == 0) || $g === null) {
            $this->DrawColor = sprintf('%.3F G', $r / 255);
        } else {
            $this->DrawColor = sprintf('%.3F %.3F %.3F RG', $r / 255, $g / 255, $b / 255);
        }
        if ($this->page > 0) {
            $this->_out($this->DrawColor);
        }
    }

    public function SetFillColor(int $r, ?int $g = null, ?int $b = null): void
    {
        if (($r == 0 && $g == 0 && $b == 0) || $g === null) {
            $this->FillColor = sprintf('%.3F g', $r / 255);
        } else {
            $this->FillColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
        }
        $this->ColorFlag = ($this->FillColor != $this->TextColor);
        if ($this->page > 0) {
            $this->_out($this->FillColor);
        }
    }

    public function SetTextColor(int $r, ?int $g = null, ?int $b = null): void
    {
        if (($r == 0 && $g == 0 && $b == 0) || $g === null) {
            $this->TextColor = sprintf('%.3F g', $r / 255);
        } else {
            $this->TextColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
        }
        $this->ColorFlag = ($this->FillColor != $this->TextColor);
    }

    public function SetLineWidth(float $width): void
    {
        $this->LineWidth = $width;
        if ($this->page > 0) {
            $this->_out(sprintf('%.2F w', $width * $this->k));
        }
    }

    public function Line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S', $x1 * $this->k, ($this->h - $y1) * $this->k, $x2 * $this->k, ($this->h - $y2) * $this->k));
    }

    public function Rect(float $x, float $y, float $w, float $h, string $style = ''): void
    {
        $op = match ($style) {
            'F' => 'f',
            'FD', 'DF' => 'B',
            default => 'S',
        };
        $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s', $x * $this->k, ($this->h - $y) * $this->k, $w * $this->k, -$h * $this->k, $op));
    }

    public function AddPage(string $orientation = '', string $size = ''): void
    {
        if ($this->state == 0) {
            $this->Open();
        }
        $family = $this->FontFamily;
        $style = $this->FontStyle . ($this->underline ? 'U' : '');
        $fontsize = $this->FontSizePt;
        $lw = $this->LineWidth;
        $dc = $this->DrawColor;
        $fc = $this->FillColor;
        $tc = $this->TextColor;
        $cf = $this->ColorFlag;

        if ($this->page > 0) {
            $this->InFooter = true;
            $this->Footer();
            $this->InFooter = false;
            $this->_endpage();
        }

        $this->_startpage($orientation, $size);

        $this->_out('2 J');
        $this->LineWidth = $lw;
        $this->_out(sprintf('%.2F w', $lw * $this->k));
        if ($family) {
            $this->SetFont($family, $style, $fontsize);
        }
        $this->DrawColor = $dc;
        if ($dc != '0 G') {
            $this->_out($dc);
        }
        $this->FillColor = $fc;
        if ($fc != '0 g') {
            $this->_out($fc);
        }
        $this->TextColor = $tc;
        $this->ColorFlag = $cf;

        $this->InHeader = true;
        $this->Header();
        $this->InHeader = false;

        if ($this->LineWidth != $lw) {
            $this->LineWidth = $lw;
            $this->_out(sprintf('%.2F w', $lw * $this->k));
        }
        if ($family) {
            $this->SetFont($family, $style, $fontsize);
        }
        if ($this->DrawColor != $dc) {
            $this->DrawColor = $dc;
            $this->_out($dc);
        }
        if ($this->FillColor != $fc) {
            $this->FillColor = $fc;
            $this->_out($fc);
        }
        $this->TextColor = $tc;
        $this->ColorFlag = $cf;
    }

    public function Header(): void
    {
        // To be implemented in child class
    }

    public function Footer(): void
    {
        // To be implemented in child class
    }

    public function SetFont(string $family, string $style = '', ?float $size = null): void
    {
        $family = strtolower($family);
        if ($family == '') {
            $family = $this->FontFamily;
        }
        if ($family == 'arial') {
            $family = 'helvetica';
        }
        $style = strtoupper($style);
        if (str_contains($style, 'U')) {
            $this->underline = true;
            $style = str_replace('U', '', $style);
        } else {
            $this->underline = false;
        }
        if ($style == 'IB') {
            $style = 'BI';
        }
        if ($size === null || $size <= 0) {
            $size = $this->FontSizePt;
        }

        if ($this->FontFamily == $family && $this->FontStyle == $style && $this->FontSizePt == $size) {
            return;
        }

        $fontkey = $family . $style;
        if (!isset($this->fonts[$fontkey])) {
            if (in_array($fontkey, $this->CoreFonts)) {
                $this->fonts[$fontkey] = [
                    'i' => count($this->fonts) + 1,
                    'type' => 'core',
                    'name' => match ($fontkey) {
                        'helveticaB' => 'Helvetica-Bold',
                        'helveticaI' => 'Helvetica-Oblique',
                        'helveticaBI' => 'Helvetica-BoldOblique',
                        'times' => 'Times-Roman',
                        'timesB' => 'Times-Bold',
                        'timesI' => 'Times-Italic',
                        'timesBI' => 'Times-BoldItalic',
                        'courier' => 'Courier',
                        'courierB' => 'Courier-Bold',
                        'courierI' => 'Courier-Oblique',
                        'courierBI' => 'Courier-BoldOblique',
                        default => 'Helvetica',
                    },
                    'up' => -100,
                    'ut' => 50,
                    'cw' => $this->_getCoreFontWidths($fontkey)
                ];
            } else {
                $fontkey = 'helvetica';
                if (!isset($this->fonts['helvetica'])) {
                    $this->fonts['helvetica'] = [
                        'i' => count($this->fonts) + 1,
                        'type' => 'core',
                        'name' => 'Helvetica',
                        'up' => -100,
                        'ut' => 50,
                        'cw' => $this->_getCoreFontWidths('helvetica')
                    ];
                }
            }
        }

        $this->FontFamily = $family;
        $this->FontStyle = $style;
        $this->FontSizePt = $size;
        $this->FontSize = $size / $this->k;
        $this->CurrentFont = $this->fonts[$fontkey];

        if ($this->page > 0) {
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
        }
    }

    public function SetFontSize(float $size): void
    {
        if ($this->FontSizePt == $size) {
            return;
        }
        $this->FontSizePt = $size;
        $this->FontSize = $size / $this->k;
        if ($this->page > 0 && isset($this->CurrentFont['i'])) {
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
        }
    }

    public function GetStringWidth(string $s): float
    {
        $cw = $this->CurrentFont['cw'] ?? [];
        $w = 0;
        $l = strlen($s);
        for ($i = 0; $i < $l; $i++) {
            $c = $s[$i];
            $w += $cw[$c] ?? 600;
        }
        return $w * $this->FontSize / 1000.0;
    }

    public function SetX(float $x): void
    {
        $this->x = ($x >= 0) ? $x : ($this->w + $x);
    }

    public function SetY(float $y, bool $resetX = true): void
    {
        if ($resetX) {
            $this->x = $this->lMargin;
        }
        $this->y = ($y >= 0) ? $y : ($this->h + $y);
    }

    public function SetXY(float $x, float $y): void
    {
        $this->SetY($y, false);
        $this->SetX($x);
    }

    public function GetX(): float
    {
        return $this->x;
    }

    public function GetY(): float
    {
        return $this->y;
    }

    public function Ln(?float $h = null): void
    {
        $this->x = $this->lMargin;
        $this->y += $h ?? $this->lasth;
    }

    public function Cell(float $w, float $h = 0, string $txt = '', mixed $border = 0, int $ln = 0, string $align = '', bool $fill = false, string $link = ''): void
    {
        $k = $this->k;
        if ($this->y + $h > $this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
            $x = $this->x;
            $ws = $this->ws;
            if ($ws > 0) {
                $this->ws = 0;
                $this->_out('0 Tw');
            }
            $this->AddPage($this->CurOrientation);
            $this->x = $x;
            if ($ws > 0) {
                $this->ws = $ws;
                $this->_out(sprintf('%.3F Tw', $ws * $k));
            }
        }
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }

        $s = '';
        if ($fill || $border == 1) {
            $op = ($fill) ? ($border == 1 ? 'B' : 'f') : 'S';
            $s = sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x * $k, ($this->h - $this->y) * $k, $w * $k, -$h * $k, $op);
        }

        if (is_string($border)) {
            $x = $this->x * $k;
            $y = ($this->h - $this->y) * $k;
            if (str_contains($border, 'L')) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x, $y, $x, $y - $h * $k);
            if (str_contains($border, 'T')) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x, $y, $x + $w * $k, $y);
            if (str_contains($border, 'R')) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x + $w * $k, $y, $x + $w * $k, $y - $h * $k);
            if (str_contains($border, 'B')) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x, $y - $h * $k, $x + $w * $k, $y - $h * $k);
        }

        if ($txt !== '') {
            $width = $this->GetStringWidth($txt);
            $dx = match ($align) {
                'R' => $w - $this->cMargin - $width,
                'C' => ($w - $width) / 2.0,
                default => $this->cMargin,
            };
            if ($this->ColorFlag) {
                $s .= 'q ' . $this->TextColor . ' ';
            }
            $txt2 = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $txt);
            $s .= sprintf('BT %.2F %.2F Td (%s) Tj ET', ($this->x + $dx) * $k, ($this->h - ($this->y + 0.5 * $h + 0.3 * $this->FontSize)) * $k, $txt2);
            if ($this->underline) {
                $s .= ' ' . $this->_dounderline($this->x + $dx, $this->y + 0.5 * $h + 0.3 * $this->FontSize, $txt);
            }
            if ($this->ColorFlag) {
                $s .= ' Q';
            }
        }

        if ($s) {
            $this->_out($s);
        }

        $this->lasth = $h;
        if ($ln > 0) {
            $this->y += $h;
            if ($ln == 1) {
                $this->x = $this->lMargin;
            }
        } else {
            $this->x += $w;
        }
    }

    public function MultiCell(float $w, float $h, string $txt, mixed $border = 0, string $align = 'J', bool $fill = false): void
    {
        $cw = $this->CurrentFont['cw'] ?? [];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") {
            $nb--;
        }
        $b = 0;
        if ($border) {
            if ($border == 1) {
                $border = 'LTRB';
                $b = 'LRT';
                $b2 = 'LR';
            } else {
                $b2 = '';
                if (str_contains($border, 'L')) $b2 .= 'L';
                if (str_contains($border, 'R')) $b2 .= 'R';
                $b = str_contains($border, 'T') ? $b2 . 'T' : $b2;
            }
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $ns = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                if ($border && $nl == 2) $b = $b2;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
                $ls = $l;
                $ns++;
            }
            $l += $cw[$c] ?? 600;
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) $i++;
                    $this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
                } else {
                    $this->Cell($w, $h, substr($s, $j, $sep - $j), $b, 2, $align, $fill);
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                if ($border && $nl == 2) $b = $b2;
            } else {
                $i++;
            }
        }
        if ($border && str_contains($border, 'B')) $b .= 'B';
        $this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
        $this->x = $this->lMargin;
    }

    public function AcceptPageBreak(): bool
    {
        return $this->AutoPageBreak;
    }

    public function Output(string $dest = '', string $name = '', bool $isUTF8 = false): string
    {
        if ($this->state < 3) {
            $this->Close();
        }
        $dest = strtoupper($dest);
        if ($dest === '') {
            $dest = 'I';
        }
        return match ($dest) {
            'I', 'D', 'S' => $this->buffer,
            default => $this->buffer,
        };
    }

    protected function Open(): void
    {
        $this->state = 1;
    }

    protected function Close(): void
    {
        if ($this->state == 3) {
            return;
        }
        if ($this->page == 0) {
            $this->AddPage();
        }
        $this->InFooter = true;
        $this->Footer();
        $this->InFooter = false;
        $this->_endpage();
        $this->_enddoc();
    }

    protected function _startpage(string $orientation, string $size): void
    {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->PageInfo[$this->page] = [];
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->FontFamily = '';
    }

    protected function _endpage(): void
    {
        $this->state = 1;
    }

    protected function _dounderline(float $x, float $y, string $txt): string
    {
        $up = $this->CurrentFont['up'] ?? -100;
        $ut = $this->CurrentFont['ut'] ?? 50;
        $w = $this->GetStringWidth($txt) + $this->ws * substr_count($txt, ' ');
        return sprintf('%.2F %.2F %.2F %.2F re f', $x * $this->k, ($this->h - ($y - $up / 1000.0 * $this->FontSize)) * $this->k, $w * $this->k, -$ut / 1000.0 * $this->FontSizePt);
    }

    protected function _out(string $s): void
    {
        if ($this->state == 2) {
            $this->pages[$this->page] .= $s . "\n";
        } elseif ($this->state == 1) {
            $this->_put($s);
        } elseif ($this->state == 0) {
            $this->Open();
            $this->_out($s);
        } elseif ($this->state == 3) {
            // Document closed
        }
    }

    protected function _put(string $s): void
    {
        $this->buffer .= $s . "\n";
    }

    protected function _newobj(?int $n = null): int
    {
        if ($n === null) {
            $this->n++;
            $n = $this->n;
        }
        $this->offsets[$n] = strlen($this->buffer);
        $this->_out($n . ' 0 obj');
        return $n;
    }

    protected function _putstream(string $data): void
    {
        $this->_out('stream');
        $this->_out($data);
        $this->_out('endstream');
    }

    protected function _putstreamobject(string $data): void
    {
        $entries = '';
        if ($this->compress) {
            $entries = '/Filter /FlateDecode ';
            $data = gzcompress($data);
        }
        $entries .= '/Length ' . strlen($data);
        $this->_newobj();
        $this->_out('<<' . $entries . '>>');
        $this->_putstream($data);
        $this->_out('endobj');
    }

    protected function _putpage(int $n): void
    {
        $this->_newobj();
        $this->_out('<</Type /Page');
        $this->_out('/Parent 1 0 R');
        $this->_out('/Resources 2 0 R');
        $this->_out('/Contents ' . ($this->n + 1) . ' 0 R>>');
        $this->_out('endobj');
        $p = $this->pages[$n];
        if (!empty($this->AliasNbPages)) {
            $alias = $this->AliasNbPages[0];
            $p = str_replace($alias, (string) $this->page, $p);
        }
        $this->_putstreamobject($p);
    }

    protected function _putpages(): void
    {
        $nb = $this->page;
        for ($n = 1; $n <= $nb; $n++) {
            $this->_putpage($n);
        }
        $this->offsets[1] = strlen($this->buffer);
        $this->_out('1 0 obj');
        $this->_out('<</Type /Pages');
        $kids = '/Kids [';
        for ($i = 0; $i < $nb; $i++) {
            $kids .= (3 + 2 * $i) . ' 0 R ';
        }
        $this->_out($kids . ']');
        $this->_out('/Count ' . $nb);
        $this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]', $this->DefPageSize[0], $this->DefPageSize[1]));
        $this->_out('>>');
        $this->_out('endobj');
    }

    protected function _putfonts(): void
    {
        foreach ($this->fonts as $k => $font) {
            $this->fonts[$k]['n'] = $this->_newobj();
            $type = $font['type'];
            $name = $font['name'];
            if ($type == 'core') {
                $this->_out('<</Type /Font');
                $this->_out('/BaseFont /' . $name);
                $this->_out('/Subtype /Type1');
                if ($name != 'Symbol' && $name != 'ZapfDingbats') {
                    $this->_out('/Encoding /WinAnsiEncoding');
                }
                $this->_out('>>');
                $this->_out('endobj');
            }
        }
    }

    protected function _putresourcedict(): void
    {
        $this->_out('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
        $this->_out('/Font <<');
        foreach ($this->fonts as $font) {
            $this->_out('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
        }
        $this->_out('>>');
        $this->_out('/XObject <<');
        $this->_out('>>');
    }

    protected function _putresources(): void
    {
        $this->_putfonts();
        $this->offsets[2] = strlen($this->buffer);
        $this->_out('2 0 obj');
        $this->_out('<<');
        $this->_putresourcedict();
        $this->_out('>>');
        $this->_out('endobj');
    }

    protected function _putinfo(): void
    {
        $this->metadata['Producer'] = 'PayPulse Financial Platform';
        $this->metadata['CreationDate'] = 'D:' . @date('YmdHis');
        foreach ($this->metadata as $key => $value) {
            $this->_out('/' . $key . ' (' . str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value) . ')');
        }
    }

    protected function _enddoc(): void
    {
        $this->state = 1;
        $this->_putheader();
        $this->_putpages();
        $this->_putresources();

        // Info
        $this->_newobj();
        $this->_out('<<');
        $this->_putinfo();
        $this->_out('>>');
        $this->_out('endobj');

        // Catalog
        $this->_newobj();
        $this->_out('<<');
        $this->_out('/Type /Catalog');
        $this->_out('/Pages 1 0 R');
        $this->_out('>>');
        $this->_out('endobj');

        // Cross-ref
        $offset = strlen($this->buffer);
        $this->_out('xref');
        $this->_out('0 ' . ($this->n + 1));
        $this->_out('0000000000 65535 f ');
        for ($i = 1; $i <= $this->n; $i++) {
            $this->_out(sprintf('%010d 00000 n ', $this->offsets[$i]));
        }

        // Trailer
        $this->_out('trailer');
        $this->_out('<<');
        $this->_out('/Size ' . ($this->n + 1));
        $this->_out('/Root ' . $this->n . ' 0 R');
        $this->_out('/Info ' . ($this->n - 1) . ' 0 R');
        $this->_out('>>');
        $this->_out('startxref');
        $this->_out((string) $offset);
        $this->_out('%%EOF');
        $this->state = 3;
    }

    protected function _putheader(): void
    {
        $this->_out('%PDF-' . $this->PDFVersion);
    }

    protected function _getCoreFontWidths(string $font): array
    {
        // Standard average font widths for Type1 standard fonts
        $w = [];
        for ($i = 0; $i < 256; $i++) {
            $c = chr($i);
            if (str_starts_with($font, 'courier')) {
                $w[$c] = 600;
            } elseif (str_starts_with($font, 'times')) {
                $w[$c] = match ($c) {
                    'i', 'j', 'l', 'r', 'f', 't', '.', ',', ':', ';', '!', '\'', '|' => 300,
                    'm', 'w', 'M', 'W' => 800,
                    ' ', '-' => 250,
                    default => 500,
                };
            } else { // Helvetica default
                $w[$c] = match ($c) {
                    'i', 'j', 'l', '.', ',', ':', ';', '!', '\'', '|' => 278,
                    'r', 'f', 't' => 333,
                    'm', 'w', 'M', 'W', '@' => 833,
                    ' ', '-' => 278,
                    '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' => 556,
                    default => 556,
                };
            }
        }
        return $w;
    }
}
