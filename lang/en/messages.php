<?php

return [
    'subtitle'        => 'Generate professional barcodes in vector format (SVG, EPS, PDF, JPEG).',

    'type'            => 'Barcode type',
    'code'            => 'Code',
    'code_ph'         => 'e.g. 9788804707264',

    'addon'           => 'Add-on (supplement)',
    'guided'          => 'Guided',
    'direct'          => 'Direct',
    'currency'        => 'Currency',
    'price'           => 'Price',
    'price_help'      => '1st digit = currency, remaining 4 = price ×100 (max 99.99). E.g. USD 24.95 → 52495.',
    'issue'           => 'Issue number',
    'issue_help'      => '2-digit supplement (00–99), used on periodicals for the issue number.',
    'addon_generated' => 'Generated add-on code',
    'addon_code'      => 'Add-on code',

    'cur_usd'         => 'US Dollar',
    'cur_cad'         => 'Canadian Dollar',
    'cur_gbp'         => 'Pound Sterling',
    'cur_aud'         => 'Australian Dollar',
    'cur_nzd'         => 'New Zealand Dollar',
    'cur_no_price'    => 'No price',

    'bar_width'       => 'Bar width',
    'w_fine'          => 'Thin',
    'w_medium'        => 'Medium',
    'w_large'         => 'Wide',
    'bar_height'      => 'Bar height',
    'show_text'       => 'Show digits below',
    'color'           => 'Color',
    'output_formats'  => 'Output formats',
    'dpi'             => 'DPI (JPEG)',
    'download'        => 'Download',

    'preview'         => 'Preview',
    'batch_title'     => 'Batch generation (CSV)',
    'batch_cols'      => 'Columns',
    'batch_note'      => 'download a ZIP',
    'batch_button'    => 'Upload and process',

    // Strings used by the JavaScript (live preview).
    'err_addon_digits' => 'Add-on: enter :n digits.',
    'err_rate_limited' => 'Too many requests, waiting a moment…',
    'err_validation'   => 'Validation error',
    'err_network'      => 'Network error',
    'err_no_format'    => 'Select at least one output format.',
];
