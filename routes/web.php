<?php

use App\Http\Controllers\BarcodeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BarcodeController::class, 'index'])->name('barcode.index');

// Generation endpoints are rate limited to protect the conversion pipeline.
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/generate', [BarcodeController::class, 'generate'])->name('barcode.generate');
    Route::post('/download', [BarcodeController::class, 'download'])->name('barcode.download');
    Route::post('/batch', [BarcodeController::class, 'batch'])->name('barcode.batch');
});
