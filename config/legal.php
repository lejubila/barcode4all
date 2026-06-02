<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dati legali del sito
    |--------------------------------------------------------------------------
    |
    | Valori usati nell'informativa privacy, nella cookie policy, nei termini
    | d'uso e nel footer. NON vanno hardcodati nelle view: impostali in .env
    | così i dati personali del titolare non finiscono nel repository.
    |
    */

    'owner_name'    => env('LEGAL_OWNER_NAME', ''),
    'contact_email' => env('LEGAL_CONTACT_EMAIL', ''),

    // Luogo/provider dell'hosting (responsabile del trattamento).
    'hosting'       => env('LEGAL_HOSTING', ''),

    // Data dell'ultimo aggiornamento dei documenti legali (es. 2026-06-02).
    'updated_at'    => env('LEGAL_UPDATED_AT', ''),
];
