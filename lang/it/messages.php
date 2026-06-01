<?php

return [
    'subtitle'        => 'Genera barcode professionali in formato vettoriale (SVG, EPS, PDF, JPEG).',

    'type'            => 'Tipo barcode',
    'code'            => 'Codice',
    'code_ph'         => 'es. 9788804707264',

    'addon'           => 'Add-on (supplemento)',
    'guided'          => 'Guidato',
    'direct'          => 'Diretto',
    'currency'        => 'Valuta',
    'price'           => 'Prezzo',
    'price_help'      => '1ª cifra = valuta, restanti 4 = prezzo ×100 (max 99,99). Es. USD 24,95 → 52495.',
    'issue'           => 'Numero edizione / fascicolo',
    'issue_help'      => 'Supplemento a 2 cifre (00–99), usato sui periodici per il numero progressivo.',
    'addon_generated' => 'Codice add-on generato',
    'addon_code'      => 'Codice add-on',

    'cur_usd'         => 'Dollaro USA',
    'cur_cad'         => 'Dollaro canadese',
    'cur_gbp'         => 'Sterlina',
    'cur_aud'         => 'Dollaro australiano',
    'cur_nzd'         => 'Dollaro neozelandese',
    'cur_no_price'    => 'Nessun prezzo',

    'bar_width'       => 'Larghezza barre',
    'w_fine'          => 'Fine',
    'w_medium'        => 'Medio',
    'w_large'         => 'Largo',
    'bar_height'      => 'Altezza barre',
    'show_text'       => 'Mostra cifre sotto',
    'color'           => 'Colore',
    'output_formats'  => 'Formati di output',
    'dpi'             => 'DPI (JPEG)',
    'download'        => 'Scarica',

    'preview'         => 'Anteprima',
    'batch_title'     => 'Generazione batch (CSV)',
    'batch_cols'      => 'Colonne',
    'batch_note'      => 'scarica uno ZIP',
    'batch_button'    => 'Carica ed elabora',

    // Messaggi usati dal JavaScript (anteprima live).
    'err_addon_digits' => 'Add-on: inserisci :n cifre.',
    'err_rate_limited' => 'Troppe richieste, attendo un istante…',
    'err_validation'   => 'Errore di validazione',
    'err_network'      => 'Errore di rete',
    'err_no_format'    => 'Seleziona almeno un formato di output.',
];
