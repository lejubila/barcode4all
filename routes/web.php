<?php

use App\Http\Controllers\BarcodeController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', [BarcodeController::class, 'index'])->name('barcode.index');

// Switch the UI language and remember the choice in the session.
Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, SetLocale::SUPPORTED, true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back(fallback: route('barcode.index'));
})->name('locale.switch');

// Generation endpoints are rate limited to protect the conversion pipeline.
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/generate', [BarcodeController::class, 'generate'])->name('barcode.generate');
    Route::post('/download', [BarcodeController::class, 'download'])->name('barcode.download');
    Route::post('/batch', [BarcodeController::class, 'batch'])->name('barcode.batch');
});
