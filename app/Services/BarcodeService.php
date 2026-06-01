<?php

namespace App\Services;

use InvalidArgumentException;
use Picqer\Barcode\Barcode;

/**
 * Generates professional, vector (SVG) barcodes.
 *
 * Supported types (application level identifiers):
 *   ISBN13, ISBN10, ISSN, EAN13, EAN8, EAN13+2, EAN13+5, UPCA, UPCE,
 *   CODE128, CODE39
 *
 * The picqer library only renders the bars; this service adds quiet zones,
 * human readable text (with proper EAN digit grouping) and the composition
 * of EAN-13 + add-on supplements, which the library does not support natively.
 */
class BarcodeService
{
    /** Application barcode types. */
    public const TYPES = [
        'ISBN13', 'ISBN10', 'ISSN', 'EAN13', 'EAN8',
        'EAN13+2', 'EAN13+5', 'UPCA', 'UPCE', 'CODE128', 'CODE39',
    ];

    /** Number of quiet-zone modules placed at the left of an EAN symbol. */
    private const EAN_QUIET_LEFT = 11;
    /** Number of quiet-zone modules placed at the right of an EAN symbol. */
    private const EAN_QUIET_RIGHT = 7;
    /** Separator between the main EAN symbol and an add-on (GS1 = 7-12). */
    private const ADDON_GAP = 9;
    /** Quiet zone for non EAN/UPC linear symbols (Code 128/39). */
    private const LINEAR_QUIET = 10;

    private RawBarcodeGenerator $raw;

    public function __construct(?RawBarcodeGenerator $raw = null)
    {
        $this->raw = $raw ?? new RawBarcodeGenerator();
    }

    /**
     * Generic dispatcher used by the controller. Returns an SVG string for
     * any supported type. For EAN13+2 / EAN13+5 the supplement is taken from
     * $options['addon'].
     */
    public function generateSVG(string $code, string $type, array $options = []): string
    {
        $type = strtoupper(trim($type));

        return match ($type) {
            'ISBN13', 'ISBN10' => $this->generateISBN($code, $options),
            'ISSN'             => $this->generateISSN($code, $options),
            'EAN13'            => $this->svgForEan13($this->normalizeEan($code, 13), $options),
            'EAN8'             => $this->svgForEan8($this->normalizeEan($code, 8), $options),
            'EAN13+2'          => $this->generateEAN13WithAddon($code, $options['addon'] ?? '', '2', $options),
            'EAN13+5'          => $this->generateEAN13WithAddon($code, $options['addon'] ?? '', '5', $options),
            'UPCA'             => $this->svgForLinear($this->onlyDigits($code), 'UPCA', $options),
            'UPCE'             => $this->svgForLinear($this->onlyDigits($code), 'UPCE', $options),
            'CODE128'          => $this->svgForLinear($code, 'C128', $options),
            'CODE39'           => $this->svgForLinear(strtoupper($code), 'C39', $options),
            default            => throw new InvalidArgumentException("Unsupported barcode type: {$type}"),
        };
    }

    /**
     * EAN-13 with a 2 or 5 digit add-on (price supplement).
     *
     * @param string $ean13  the 12 or 13 digit base code
     * @param string $addon  supplement digits ("99" or "15999")
     * @param string $format "2" or "5"
     */
    public function generateEAN13WithAddon(string $ean13, string $addon, string $format = '5', array $options = []): string
    {
        $base = $this->normalizeEan($ean13, 13);
        $addon = $this->onlyDigits($addon);
        $len = $format === '2' ? 2 : 5;

        if (strlen($addon) !== $len) {
            throw new InvalidArgumentException("The add-on must contain exactly {$len} digits.");
        }

        return $this->svgForEan13($base, $options, null, $addon, $len === 2 ? 'EAN2' : 'EAN5');
    }

    /**
     * ISBN-13 (978/979 prefix) is an EAN-13. Accepts ISBN-10 or ISBN-13,
     * with or without hyphens/spaces, computing the check digit when missing
     * and rejecting an explicit but invalid check digit.
     */
    public function generateISBN(string $isbn, array $options = []): string
    {
        $raw = strtoupper(preg_replace('/[^0-9X]/', '', $isbn));
        $len = strlen($raw);

        if ($len === 13) {
            $core12 = substr($raw, 0, 12);
            if ((string) $this->ean13Check($core12) !== $raw[12]) {
                throw new InvalidArgumentException('Invalid ISBN-13 check digit.');
            }
        } elseif ($len === 12) {
            $core12 = $raw;
        } elseif ($len === 10) {
            if (! $this->isbn10IsValid($raw)) {
                throw new InvalidArgumentException('Invalid ISBN-10 check digit.');
            }
            $core12 = '978' . substr($raw, 0, 9);
        } elseif ($len === 9) {
            $core12 = '978' . $raw;
        } else {
            throw new InvalidArgumentException('An ISBN must contain 9, 10, 12 or 13 characters.');
        }

        $ean13 = $core12 . $this->ean13Check($core12);
        $caption = 'ISBN ' . $this->hyphenateIsbn($ean13);

        return $this->svgForEan13($ean13, $options, $caption);
    }

    /**
     * ISSN -> EAN-13 with the "977" prefix (+ 2 variant digits + EAN check).
     * Accepts the 7 base digits or the full 8 character ISSN (check digit,
     * possibly "X"); an explicit but invalid ISSN check digit is rejected.
     */
    public function generateISSN(string $issn, array $options = []): string
    {
        $raw = strtoupper(preg_replace('/[^0-9X]/', '', $issn));
        $len = strlen($raw);

        if ($len === 8) {
            $core7 = substr($raw, 0, 7);
            if ((string) $this->issnCheck($core7) !== $raw[7]) {
                throw new InvalidArgumentException('Invalid ISSN check digit.');
            }
        } elseif ($len === 7) {
            $core7 = $raw;
        } else {
            throw new InvalidArgumentException('An ISSN must contain 7 or 8 characters.');
        }

        $core12 = '977' . $core7 . '00';
        $ean13 = $core12 . $this->ean13Check($core12);

        $issnCheck = $this->issnCheck($core7);
        $caption = 'ISSN ' . substr($core7, 0, 4) . '-' . substr($core7, 4, 3) . $issnCheck;

        return $this->svgForEan13($ean13, $options, $caption);
    }

    // ---------------------------------------------------------------------
    // Rendering
    // ---------------------------------------------------------------------

    /**
     * Render an EAN-13 symbol with grouped human readable digits, an optional
     * top caption (ISBN/ISSN) and an optional add-on supplement.
     */
    private function svgForEan13(string $ean13, array $options, ?string $caption = null, ?string $addon = null, ?string $addonType = null): string
    {
        $o = $this->options($options);
        $mw = $o['module'];
        $bh = $o['height'];
        $font = max(8.0, round($o['module'] * 6.5, 1));
        $captionFont = round($font * 1.05, 1);

        $main = $this->raw->getRaw($ean13, 'EAN13'); // 95 modules

        $hasTop = $caption !== null || $addon !== null;
        $topH = $hasTop ? $captionFont + 6 : 4;
        $bottomH = $o['show_text'] ? $font + 6 : 4;
        $barTop = $topH;
        $height = $barTop + $bh + $bottomH;

        // Horizontal layout (in modules)
        $mainStart = self::EAN_QUIET_LEFT;
        $addonBc = null;
        $addonStart = 0;
        if ($addon !== null) {
            $addonBc = $this->raw->getRaw($addon, $addonType);
            $addonStart = $mainStart + $main->getWidth() + self::ADDON_GAP;
            $totalMod = $addonStart + $addonBc->getWidth() + 5;
        } else {
            $totalMod = $mainStart + $main->getWidth() + self::EAN_QUIET_RIGHT;
        }
        $width = round($totalMod * $mw, 3);

        $bars = $this->renderBars($main, $mw, $bh, $mainStart, $barTop);
        if ($addonBc !== null) {
            // Add-on bars are slightly shorter and leave room for the digits above.
            $bars .= $this->renderBars($addonBc, $mw, $bh, $addonStart, $barTop);
        }

        $texts = '';

        // Top caption (ISBN / ISSN), centered over the main symbol.
        if ($caption !== null) {
            $cx = ($mainStart + $main->getWidth() / 2) * $mw;
            $texts .= $this->text($caption, $cx, $captionFont, $captionFont, $o['color'], 'middle');
        }

        // Add-on digits printed above the supplement bars.
        if ($addonBc !== null) {
            $ax = ($addonStart + $addonBc->getWidth() / 2) * $mw;
            $texts .= $this->text($addon, $ax, $captionFont, $captionFont, $o['color'], 'middle');
        }

        // Main human readable digits, grouped EAN-13 style.
        if ($o['show_text']) {
            $ty = $barTop + $bh + $font;
            // Leading digit, in the left quiet zone.
            $texts .= $this->text($ean13[0], 6 * $mw, $ty, $font, $o['color'], 'middle');
            // Left group: digits 2-7 under modules 3..44 (centre of each 7-module cell).
            for ($i = 0; $i < 6; $i++) {
                $cx = ($mainStart + 6.5 + 7 * $i) * $mw;
                $texts .= $this->text($ean13[1 + $i], $cx, $ty, $font, $o['color'], 'middle');
            }
            // Right group: digits 8-13 under modules 50..91.
            for ($i = 0; $i < 6; $i++) {
                $cx = ($mainStart + 53.5 + 7 * $i) * $mw;
                $texts .= $this->text($ean13[7 + $i], $cx, $ty, $font, $o['color'], 'middle');
            }
        }

        return $this->svgDocument($width, $height, $bars, $texts, $o['color'], $ean13);
    }

    /** Render an EAN-8 symbol with grouped (4 + 4) human readable digits. */
    private function svgForEan8(string $ean8, array $options): string
    {
        $o = $this->options($options);
        $mw = $o['module'];
        $bh = $o['height'];
        $font = max(8.0, round($o['module'] * 6.5, 1));

        $bc = $this->raw->getRaw($ean8, 'EAN8'); // 67 modules
        $quiet = 7;
        $bottomH = $o['show_text'] ? $font + 6 : 4;
        $barTop = 4;
        $height = $barTop + $bh + $bottomH;
        $width = round(($bc->getWidth() + 2 * $quiet) * $mw, 3);

        $bars = $this->renderBars($bc, $mw, $bh, $quiet, $barTop);

        $texts = '';
        if ($o['show_text']) {
            $ty = $barTop + $bh + $font;
            for ($i = 0; $i < 4; $i++) {
                $cx = ($quiet + 6.5 + 7 * $i) * $mw;
                $texts .= $this->text($ean8[$i], $cx, $ty, $font, $o['color'], 'middle');
            }
            for ($i = 0; $i < 4; $i++) {
                $cx = ($quiet + 39.5 + 7 * $i) * $mw;
                $texts .= $this->text($ean8[4 + $i], $cx, $ty, $font, $o['color'], 'middle');
            }
        }

        return $this->svgDocument($width, $height, $bars, $texts, $o['color'], $ean8);
    }

    /** Render any other linear symbol with a single centered caption below. */
    private function svgForLinear(string $code, string $picqerType, array $options): string
    {
        $o = $this->options($options);
        $mw = $o['module'];
        $bh = $o['height'];
        $font = max(8.0, round($o['module'] * 6.5, 1));

        $bc = $this->raw->getRaw($code, $picqerType);
        $quiet = self::LINEAR_QUIET;
        $bottomH = $o['show_text'] ? $font + 6 : 4;
        $barTop = 4;
        $height = $barTop + $bh + $bottomH;
        $width = round(($bc->getWidth() + 2 * $quiet) * $mw, 3);

        $bars = $this->renderBars($bc, $mw, $bh, $quiet, $barTop);

        $texts = '';
        if ($o['show_text']) {
            $ty = $barTop + $bh + $font;
            $texts .= $this->text($code, $width / 2, $ty, $font, $o['color'], 'middle');
        }

        return $this->svgDocument($width, $height, $bars, $texts, $o['color'], $code);
    }

    /** Convert a raw Barcode object into SVG <rect> bars. */
    private function renderBars(Barcode $bc, float $mw, float $bh, float $xModule, float $yTop): string
    {
        $svg = '';
        $x = $xModule * $mw;
        foreach ($bc->getBars() as $bar) {
            $bw = $bar->getWidth() * $mw;
            if ($bar->isBar() && $bw > 0) {
                $svg .= sprintf(
                    '        <rect x="%s" y="%s" width="%s" height="%s"/>' . "\n",
                    round($x, 3), round($yTop, 3), round($bw, 3), round($bh, 3)
                );
            }
            $x += $bw;
        }

        return $svg;
    }

    private function text(string $value, float $x, float $y, float $size, string $color, string $anchor = 'start'): string
    {
        return sprintf(
            '        <text x="%s" y="%s" font-family="monospace" font-size="%s" fill="%s" text-anchor="%s">%s</text>' . "\n",
            round($x, 3), round($y, 3), $size, $color, $anchor, htmlspecialchars($value, ENT_QUOTES)
        );
    }

    private function svgDocument(float $width, float $height, string $bars, string $texts, string $color, string $code): string
    {
        $w = round($width, 3);
        $h = round($height, 3);

        return '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . "\n"
            . sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 %s %s" version="1.1">' . "\n", $w, $h, $w, $h)
            . '    <desc>' . htmlspecialchars($code, ENT_QUOTES) . '</desc>' . "\n"
            . sprintf('    <rect x="0" y="0" width="%s" height="%s" fill="white"/>' . "\n", $w, $h)
            . sprintf('    <g fill="%s" stroke="none">' . "\n", $color)
            . $bars
            . '    </g>' . "\n"
            . $texts
            . '</svg>' . "\n";
    }

    // ---------------------------------------------------------------------
    // Options & helpers
    // ---------------------------------------------------------------------

    private function options(array $options): array
    {
        // Module width presets for the "bar width" slider.
        $presets = ['fine' => 1.0, 'medio' => 2.0, 'largo' => 3.0];
        $module = $options['module'] ?? ($presets[$options['width'] ?? 'medio'] ?? 2.0);

        return [
            'module'    => (float) max(0.5, (float) $module),
            'height'    => (float) max(10, $options['height'] ?? 60),
            'show_text' => array_key_exists('show_text', $options) ? (bool) $options['show_text'] : true,
            'color'     => $this->sanitizeColor($options['color'] ?? '#000000'),
        ];
    }

    private function sanitizeColor(string $color): string
    {
        return preg_match('/^#[0-9a-fA-F]{3,8}$|^[a-zA-Z]+$/', $color) ? $color : '#000000';
    }

    private function onlyDigits(string $value): string
    {
        return preg_replace('/\D/', '', $value);
    }

    /** Normalize an EAN code: keep digits, compute check digit when missing. */
    private function normalizeEan(string $code, int $length): string
    {
        $digits = $this->onlyDigits($code);
        $dataLen = $length - 1;

        if (strlen($digits) === $dataLen) {
            return $length === 13
                ? $digits . $this->ean13Check($digits)
                : $digits . $this->ean8Check($digits);
        }

        if (strlen($digits) === $length) {
            return $digits;
        }

        throw new InvalidArgumentException("An EAN-{$length} requires {$dataLen} or {$length} digits.");
    }

    /** EAN-13 / ISBN-13 check digit (weights 1,3 alternating, mod 10). */
    public function ean13Check(string $digits12): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $digits12[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        return (10 - $sum % 10) % 10;
    }

    /** EAN-8 check digit (weights 3,1 alternating, mod 10). */
    public function ean8Check(string $digits7): int
    {
        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $sum += (int) $digits7[$i] * ($i % 2 === 0 ? 3 : 1);
        }

        return (10 - $sum % 10) % 10;
    }

    /** Validate a 10 character ISBN-10 (weights 10..1, mod 11, X = 10). */
    public function isbn10IsValid(string $isbn10): bool
    {
        if (strlen($isbn10) !== 10) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $c = $isbn10[$i];
            $val = $c === 'X' ? 10 : (int) $c;
            $sum += (10 - $i) * $val;
        }

        return $sum % 11 === 0;
    }

    /** ISSN check digit over the 7 base digits (weights 8..2, mod 11). */
    public function issnCheck(string $digits7): string
    {
        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $sum += (8 - $i) * (int) $digits7[$i];
        }
        $rem = $sum % 11;
        $check = (11 - $rem) % 11;

        return $check === 10 ? 'X' : (string) $check;
    }

    /** Best-effort ISBN hyphenation for the caption (EAN prefix + check only). */
    private function hyphenateIsbn(string $ean13): string
    {
        return substr($ean13, 0, 3) . '-' . substr($ean13, 3, 9) . '-' . substr($ean13, 12, 1);
    }
}
