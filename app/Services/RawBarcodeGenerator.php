<?php

namespace App\Services;

use Picqer\Barcode\Barcode;
use Picqer\Barcode\BarcodeGenerator;

/**
 * Thin subclass that exposes the protected getBarcodeData() of the picqer
 * generator so we can obtain the raw bar/space sequence (a Barcode object)
 * and render it ourselves. This is required to compose EAN-13 + add-on
 * symbols and to draw human readable text, which the bundled renderers do
 * not provide.
 */
class RawBarcodeGenerator extends BarcodeGenerator
{
    public function getRaw(string $code, string $type): Barcode
    {
        return $this->getBarcodeData($code, $type);
    }
}
