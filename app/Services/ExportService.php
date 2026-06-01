<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use DOMDocument;
use RuntimeException;

/**
 * Converts a generated SVG barcode into the various downloadable formats.
 *
 *   SVG  -> returned as-is by BarcodeService
 *   PDF  -> DOMPDF (vector)
 *   EPS  -> native PostScript (vector, standard Courier font, no embedding)
 *   JPEG -> ImageMagick at the requested DPI (raster)
 *
 * All intermediate files live in storage/app/temp and the public methods
 * return the absolute path of the produced file. Callers are responsible for
 * deleting the returned file after sending it (deleteFileAfterSend()).
 */
class ExportService
{
    public function tempDir(): string
    {
        $dir = storage_path('app/temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }

    public function saveSvg(string $svgContent, string $filename): string
    {
        $path = $this->tempDir() . '/' . $this->safe($filename) . '.svg';
        file_put_contents($path, $svgContent);

        return $path;
    }

    /** Render the SVG into a tightly-cropped vector PDF using DOMPDF. */
    public function toPDF(string $svgContent, string $filename): string
    {
        [$w, $h] = $this->svgDimensions($svgContent);

        $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svgContent);
        $html = '<!DOCTYPE html><html><head><style>'
            . '@page { margin: 0; } html,body { margin:0; padding:0; }'
            . 'img { display:block; }'
            . '</style></head><body>'
            . sprintf('<img src="%s" style="width:%spx;height:%spx;">', $dataUri, $w, $h)
            . '</body></html>';

        // CSS px -> PDF points (DOMPDF default 96 DPI => 0.75pt per px).
        $paper = [0, 0, round($w * 0.75, 2), round($h * 0.75, 2)];

        $pdf = Pdf::loadHTML($html)->setPaper($paper);

        $path = $this->tempDir() . '/' . $this->safe($filename) . '.pdf';
        $pdf->save($path);

        return $path;
    }

    /**
     * Render a compact, native EPS straight from our own SVG.
     *
     * A barcode is just rectangles plus a few characters, so we emit them as
     * PostScript "rectfill" operators and draw the text with the built-in
     * Courier font (one of the standard PostScript fonts, never embedded).
     * This avoids the DOMPDF + Ghostscript path, whose font embedding produced
     * ~160 KB files; the native output is only a few KB and fully vector.
     */
    public function toEPS(string $svgContent, string $filename): string
    {
        $eps = $this->svgToEps($svgContent);
        $epsPath = $this->tempDir() . '/' . $this->safe($filename) . '.eps';
        file_put_contents($epsPath, $eps);

        return $epsPath;
    }

    /** Translate our generated SVG (rects + texts) into EPS PostScript. */
    private function svgToEps(string $svg): string
    {
        [$w, $h] = $this->svgDimensions($svg);

        $doc = new DOMDocument();
        // Our SVG is well-formed; suppress libxml notices on the XML/DOCTYPE.
        libxml_use_internal_errors(true);
        $doc->loadXML($svg);
        libxml_clear_errors();

        $body = '';

        // Bars: every <rect> except the white background. The bar colour is the
        // fill of the enclosing <g>.
        foreach ($doc->getElementsByTagName('rect') as $rect) {
            $fill = $rect->getAttribute('fill');
            if (strtolower($fill) === 'white') {
                continue; // background
            }
            $color = $fill !== '' ? $fill : ($rect->parentNode?->getAttribute('fill') ?: '#000000');
            $x = (float) $rect->getAttribute('x');
            $y = (float) $rect->getAttribute('y');
            $rw = (float) $rect->getAttribute('width');
            $rh = (float) $rect->getAttribute('height');

            [$r, $g, $b] = $this->hexToRgb($color);
            // SVG origin is top-left, PostScript bottom-left: flip Y.
            $body .= sprintf("%s %s %s setrgbcolor\n", $this->ps($r), $this->ps($g), $this->ps($b));
            $body .= sprintf("%s %s %s %s rectfill\n", $this->ps($x), $this->ps($h - $y - $rh), $this->ps($rw), $this->ps($rh));
        }

        // Human readable text, drawn with Courier at the same baseline.
        foreach ($doc->getElementsByTagName('text') as $text) {
            $value = $text->textContent;
            if ($value === '') {
                continue;
            }
            $x = (float) $text->getAttribute('x');
            $y = (float) $text->getAttribute('y');
            $size = (float) ($text->getAttribute('font-size') ?: 10);
            $anchor = $text->getAttribute('text-anchor') ?: 'start';
            [$r, $g, $b] = $this->hexToRgb($text->getAttribute('fill') ?: '#000000');
            $escaped = $this->psString($value);

            $body .= sprintf("/Courier findfont %s scalefont setfont\n", $this->ps($size));
            $body .= sprintf("%s %s %s setrgbcolor\n", $this->ps($r), $this->ps($g), $this->ps($b));
            $baseline = $this->ps($h - $y);
            if ($anchor === 'middle') {
                // Centre on x: subtract half the rendered string width.
                $body .= sprintf("%s %s moveto %s dup stringwidth pop 2 div neg 0 rmoveto show\n",
                    $this->ps($x), $baseline, $escaped);
            } else {
                $body .= sprintf("%s %s moveto %s show\n", $this->ps($x), $baseline, $escaped);
            }
        }

        $header = "%!PS-Adobe-3.0 EPSF-3.0\n"
            . "%%Creator: Barcode Generator Pro\n"
            . sprintf("%%%%BoundingBox: 0 0 %d %d\n", (int) ceil($w), (int) ceil($h))
            . sprintf("%%%%HiResBoundingBox: 0 0 %s %s\n", $this->ps($w), $this->ps($h))
            . "%%LanguageLevel: 2\n"
            . "%%EndComments\n";

        return $header . $body . "showpage\n%%EOF\n";
    }

    /** Format a float for PostScript (trim trailing zeros, max 3 decimals). */
    private function ps(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.') ?: '0';
    }

    /** Escape a string for a PostScript literal "(...)". */
    private function psString(string $value): string
    {
        return '(' . str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value) . ')';
    }

    /** Convert an SVG colour to PostScript RGB floats (0-1); fallback black. */
    private function hexToRgb(string $color): array
    {
        $color = ltrim(trim($color), '#');
        if (strlen($color) === 3) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }
        if (! preg_match('/^[0-9a-fA-F]{6}/', $color)) {
            return [0.0, 0.0, 0.0]; // named colours / unknown -> black
        }

        return [
            hexdec(substr($color, 0, 2)) / 255,
            hexdec(substr($color, 2, 2)) / 255,
            hexdec(substr($color, 4, 2)) / 255,
        ];
    }

    /**
     * Rasterize the SVG to JPEG at the requested DPI.
     *
     * librsvg (rsvg-convert) renders the SVG to a high-resolution PNG, then
     * ImageMagick produces the final JPEG. This is far more reliable than
     * letting ImageMagick decode the SVG itself. When librsvg is unavailable
     * we fall back to ImageMagick's own (weaker) SVG support.
     */
    public function toJPEG(string $svgContent, int $dpi, string $filename): string
    {
        $dpi = max(72, min(1200, $dpi));
        $svgPath = $this->saveSvg($svgContent, $filename . '_src');
        $jpgPath = $this->tempDir() . '/' . $this->safe($filename) . '.jpg';
        $binary = $this->imagemagickBinary();

        if ($this->binaryExists('rsvg-convert')) {
            $pngPath = $this->tempDir() . '/' . $this->safe($filename) . '_src.png';
            // The SVG uses px units (96 dpi); zoom to reach the target DPI.
            $zoom = round($dpi / 96, 4);
            $this->run(
                sprintf('rsvg-convert -z %s -b white -f png -o %s %s 2>&1', $zoom, escapeshellarg($pngPath), escapeshellarg($svgPath)),
                $pngPath,
                'librsvg (SVG->PNG) conversion failed'
            );
            $this->run(
                sprintf('%s %s -background white -flatten -quality 92 %s 2>&1', $binary, escapeshellarg($pngPath), escapeshellarg($jpgPath)),
                $jpgPath,
                'ImageMagick (PNG->JPEG) conversion failed'
            );
            @unlink($pngPath);
        } else {
            $this->run(
                sprintf('%s -density %d -background white %s -flatten -quality 92 %s 2>&1', $binary, $dpi, escapeshellarg($svgPath), escapeshellarg($jpgPath)),
                $jpgPath,
                'ImageMagick (JPEG) conversion failed'
            );
        }

        @unlink($svgPath);

        return $jpgPath;
    }

    /** Produce the file for an arbitrary format and return [path, mime]. */
    public function export(string $svgContent, string $format, string $filename, int $dpi = 300): array
    {
        return match (strtolower($format)) {
            'svg'  => [$this->saveSvg($svgContent, $filename), 'image/svg+xml'],
            'pdf'  => [$this->toPDF($svgContent, $filename), 'application/pdf'],
            'eps'  => [$this->toEPS($svgContent, $filename), 'application/postscript'],
            'jpeg', 'jpg' => [$this->toJPEG($svgContent, $dpi, $filename), 'image/jpeg'],
            default => throw new RuntimeException("Unsupported format: {$format}"),
        };
    }

    // ---------------------------------------------------------------------

    private function run(string $cmd, string $expectedFile, string $error): void
    {
        exec($cmd, $output, $code);
        if ($code !== 0 || ! file_exists($expectedFile) || filesize($expectedFile) === 0) {
            throw new RuntimeException($error . ': ' . implode("\n", (array) $output));
        }
    }

    private function binaryExists(string $binary): bool
    {
        exec('command -v ' . escapeshellarg($binary), $o, $c);

        return $c === 0;
    }

    private function imagemagickBinary(): string
    {
        // ImageMagick 7 uses "magick"; older versions use "convert".
        exec('command -v magick', $o1, $c1);
        if ($c1 === 0) {
            return 'magick';
        }

        return 'convert';
    }

    /** @return array{0:float,1:float} width/height parsed from the SVG header. */
    private function svgDimensions(string $svg): array
    {
        $w = 200.0;
        $h = 80.0;
        if (preg_match('/<svg[^>]*\bwidth="([\d.]+)"/', $svg, $mw)) {
            $w = (float) $mw[1];
        }
        if (preg_match('/<svg[^>]*\bheight="([\d.]+)"/', $svg, $mh)) {
            $h = (float) $mh[1];
        }

        return [$w, $h];
    }

    private function safe(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);

        return $name !== '' ? $name : 'barcode';
    }
}
