<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateBarcodeRequest;
use App\Services\BarcodeService;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class BarcodeController extends Controller
{
    public function __construct(
        private readonly BarcodeService $barcodes,
        private readonly ExportService $exporter,
    ) {
    }

    /** Show the generator form. */
    public function index()
    {
        return view('barcode.index', [
            'types' => BarcodeService::TYPES,
        ]);
    }

    /** AJAX live preview: returns the SVG (and a data URI) as JSON. */
    public function generate(GenerateBarcodeRequest $request): JsonResponse
    {
        try {
            $svg = $this->barcodes->generateSVG(
                $request->string('code')->toString(),
                $request->string('type')->toString(),
                $request->barcodeOptions(),
            );
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok'      => true,
            'svg'     => $svg,
            'dataUri' => 'data:image/svg+xml;base64,' . base64_encode($svg),
        ]);
    }

    /** Generate and download the barcode in one or more formats. */
    public function download(GenerateBarcodeRequest $request): BinaryFileResponse
    {
        $formats = $request->input('formats', ['svg']);
        $dpi = (int) $request->input('dpi', 300);

        try {
            $svg = $this->barcodes->generateSVG(
                $request->string('code')->toString(),
                $request->string('type')->toString(),
                $request->barcodeOptions(),
            );
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['code' => $e->getMessage()]);
        }

        $base = $this->filenameFor($request->string('type')->toString(), $request->string('code')->toString());

        // A single format streams the file directly; several produce a ZIP.
        if (count($formats) === 1) {
            [$path, $mime] = $this->exporter->export($svg, $formats[0], $base, $dpi);

            return response()->download($path, basename($path), ['Content-Type' => $mime])
                ->deleteFileAfterSend(true);
        }

        $files = [];
        foreach ($formats as $format) {
            [$path] = $this->exporter->export($svg, $format, $base, $dpi);
            $files[] = $path;
        }

        $zip = $this->makeZip($files, $base);

        return response()->download($zip, $base . '.zip')->deleteFileAfterSend(true);
    }

    /** Batch generation from an uploaded CSV (type,code,addon,output_format). */
    public function batch(Request $request): BinaryFileResponse
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $handle = fopen($request->file('csv')->getRealPath(), 'r');
        $files = [];
        $errors = [];
        $row = 0;

        // Optional header row: type,code,addon,output_format
        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            $type = trim($data[0] ?? '');
            if ($type === '' || strtolower($type) === 'type') {
                continue; // skip empty lines and the header
            }
            $code = trim($data[1] ?? '');
            $addon = trim($data[2] ?? '');
            $format = strtolower(trim($data[3] ?? 'svg')) ?: 'svg';

            try {
                $options = $addon !== '' ? ['addon' => $addon] : [];
                $svg = $this->barcodes->generateSVG($code, $type, $options);
                $base = $this->filenameFor($type, $code) . '_' . $row;
                [$path] = $this->exporter->export($svg, $format, $base, 300);
                $files[] = $path;
            } catch (\Throwable $e) {
                $errors[] = sprintf('Row %d (%s %s): %s', $row, $type, $code, $e->getMessage());
            }
        }
        fclose($handle);

        if (empty($files)) {
            throw ValidationException::withMessages([
                'csv' => 'No barcode could be generated. ' . implode(' ', $errors),
            ]);
        }

        $zip = $this->makeZip($files, 'barcodes_batch', $errors);

        return response()->download($zip, 'barcodes_batch.zip')->deleteFileAfterSend(true);
    }

    // ---------------------------------------------------------------------

    /** @param string[] $files */
    private function makeZip(array $files, string $base, array $errors = []): string
    {
        $zipPath = $this->exporter->tempDir() . '/' . Str::slug($base) . '_' . Str::random(6) . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }
        if (! empty($errors)) {
            $zip->addFromString('errors.txt', implode("\n", $errors));
        }
        $zip->close();

        // The individual files have been embedded; remove the temporaries.
        foreach ($files as $file) {
            @unlink($file);
        }

        return $zipPath;
    }

    private function filenameFor(string $type, string $code): string
    {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', $code);

        return strtolower($type) . '_' . ($clean !== '' ? $clean : 'barcode');
    }
}
