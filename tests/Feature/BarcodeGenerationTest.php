<?php

namespace Tests\Feature;

use App\Services\BarcodeService;
use App\Services\ExportService;
use InvalidArgumentException;
use Tests\TestCase;

class BarcodeGenerationTest extends TestCase
{
    private function service(): BarcodeService
    {
        return new BarcodeService();
    }

    public function test_generates_a_valid_isbn13_svg(): void
    {
        $svg = $this->service()->generateISBN('978-0-306-40615-7');

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('http://www.w3.org/2000/svg', $svg);
        $this->assertStringContainsString('ISBN', $svg);
        $this->assertStringContainsString('<rect', $svg);
    }

    public function test_converts_isbn10_to_isbn13(): void
    {
        // 0-306-40615-2 is the ISBN-10 of the ISBN-13 above.
        $svg = $this->service()->generateISBN('0-306-40615-2');

        $this->assertStringContainsString('978', $svg);
    }

    public function test_rejects_isbn_with_wrong_check_digit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service()->generateISBN('978-0-306-40615-8'); // 8 should be 7
    }

    public function test_generates_ean13_with_5_digit_addon(): void
    {
        $svg = $this->service()->generateEAN13WithAddon('5901234123457', '52495', '5');

        $this->assertStringContainsString('<svg', $svg);
        // Add-on digits appear as a text element.
        $this->assertStringContainsString('52495', $svg);
        // Two symbols => the document is wider than a bare EAN-13.
        $this->assertMatchesRegularExpression('/width="(\d+(\.\d+)?)"/', $svg);
    }

    public function test_rejects_addon_of_wrong_length(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service()->generateEAN13WithAddon('5901234123457', '123', '5');
    }

    public function test_issn_is_encoded_with_977_prefix(): void
    {
        $svg = $this->service()->generateISSN('0317-8471');

        $this->assertStringContainsString('ISSN', $svg);
        $this->assertStringContainsString('<rect', $svg);
    }

    public function test_converts_svg_to_pdf(): void
    {
        $svg = $this->service()->generateSVG('5901234123457', 'EAN13');
        $path = app(ExportService::class)->toPDF($svg, 'test_ean13');

        $this->assertFileExists($path);
        $this->assertStringEqualsFile($path, file_get_contents($path));
        $this->assertStringStartsWith('%PDF', file_get_contents($path));

        @unlink($path);
    }

    public function test_converts_svg_to_eps(): void
    {
        $svg = $this->service()->generateSVG('5901234123457', 'EAN13');
        $path = app(ExportService::class)->toEPS($svg, 'test_ean13');
        $contents = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('%!PS-Adobe', $contents);
        // Native vector output: bars are PostScript rectangles, not embedded fonts.
        $this->assertStringContainsString('rectfill', $contents);
        // Regression guard: the bloated DOMPDF+Ghostscript path produced ~160 KB.
        $this->assertLessThan(10000, filesize($path));

        @unlink($path);
    }

    public function test_downloads_jpeg_at_300_dpi(): void
    {
        if (! $this->binaryExists('convert') && ! $this->binaryExists('magick')) {
            $this->markTestSkipped('ImageMagick not available.');
        }

        $svg = $this->service()->generateSVG('5901234123457', 'EAN13');
        $path = app(ExportService::class)->toJPEG($svg, 300, 'test_ean13');

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));
        // JPEG magic bytes.
        $this->assertSame("\xFF\xD8\xFF", substr(file_get_contents($path), 0, 3));

        @unlink($path);
    }

    public function test_http_preview_endpoint_returns_svg(): void
    {
        $response = $this->postJson(route('barcode.generate'), [
            'type' => 'EAN13',
            'code' => '5901234123457',
        ]);

        $response->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonStructure(['ok', 'svg', 'dataUri']);
    }

    private function binaryExists(string $binary): bool
    {
        exec('command -v ' . escapeshellarg($binary), $out, $code);

        return $code === 0;
    }
}
