<?php

return [
    'updated'    => 'Ultimo aggiornamento: :date',
    'no_date'    => 'da definire',
    'home'       => 'Torna alla home',

    'privacy' => [
        'title' => 'Informativa sulla privacy',
        'intro' => 'La presente informativa descrive il trattamento dei dati personali degli utenti che consultano e utilizzano questo sito, ai sensi degli articoli 13 e 14 del Regolamento (UE) 2016/679 (GDPR).',

        'controller_t' => 'Titolare del trattamento',
        'controller'   => 'Il titolare del trattamento è :name. Per qualsiasi richiesta relativa ai tuoi dati puoi scrivere a :email.',

        'data_t' => 'Dati trattati',
        'data'   => 'Dati di navigazione: per il funzionamento del sito vengono trattati l\'indirizzo IP e i dati tecnici di connessione (nei log del server e per la limitazione delle richieste a fini di sicurezza). Dati inseriti per generare i barcode: i codici e gli eventuali file CSV caricati vengono utilizzati esclusivamente per produrre il risultato richiesto; i file temporanei sono eliminati subito dopo il download e non vengono conservati. Cookie tecnici: vedi la Cookie policy.',

        'purpose_t' => 'Finalità e base giuridica',
        'purpose'   => 'I dati sono trattati per erogare il servizio richiesto (generazione e download dei barcode) e per garantire la sicurezza e il corretto funzionamento del sito. La base giuridica è l\'esecuzione del servizio richiesto dall\'utente e il legittimo interesse del titolare a proteggere l\'infrastruttura. I cookie tecnici sono necessari e non richiedono consenso.',

        'retention_t' => 'Conservazione',
        'retention'   => 'I dati di navigazione e i log sono conservati per il tempo strettamente necessario alle finalità di sicurezza e poi cancellati o anonimizzati. I codici e i file caricati non sono conservati: vengono elaborati in memoria/temporaneamente ed eliminati al termine dell\'operazione. La sessione ha una durata limitata.',

        'recipients_t' => 'Destinatari e luogo del trattamento',
        'recipients'   => 'Il sito è ospitato su :hosting, che agisce come responsabile del trattamento per conto del titolare. I dati sono trattati all\'interno dell\'Unione Europea e non sono oggetto di trasferimento verso paesi extra-UE. I dati non sono ceduti a terzi né utilizzati per finalità diverse da quelle indicate.',

        'rights_t' => 'I tuoi diritti',
        'rights'   => 'Hai il diritto di accedere ai tuoi dati e di chiederne la rettifica, la cancellazione, la limitazione o l\'opposizione al trattamento, nonché il diritto alla portabilità, secondo gli articoli 15-22 del GDPR. Puoi esercitare questi diritti scrivendo a :email. Hai inoltre il diritto di proporre reclamo all\'Autorità Garante per la protezione dei dati personali (www.garanteprivacy.it).',

        'profiling_t' => 'Assenza di profilazione',
        'profiling'   => 'Il sito non effettua profilazione né processi decisionali automatizzati e non utilizza strumenti di analisi o tracciamento.',
    ],

    'cookie' => [
        'title' => 'Cookie policy',
        'intro' => 'Questo sito utilizza esclusivamente cookie tecnici, necessari al suo funzionamento. Non vengono utilizzati cookie di profilazione, di analisi o di terze parti; per questo motivo non è richiesto alcun consenso preventivo.',

        'tech_t'    => 'Cookie tecnici utilizzati',
        'c_session' => 'laravel_session — mantiene la sessione di navigazione (ad es. la lingua scelta). Durata: la sessione; cookie di prima parte.',
        'c_xsrf'    => 'XSRF-TOKEN — protegge i moduli da attacchi CSRF. Durata: la sessione; cookie di prima parte.',

        'noconsent' => 'Trattandosi di cookie tecnici, ai sensi delle Linee guida del Garante non è necessario un banner di consenso: questa pagina assolve l\'obbligo di informativa.',

        'manage_t' => 'Gestione dei cookie',
        'manage'   => 'Puoi eliminare o bloccare i cookie dalle impostazioni del tuo browser; la disabilitazione dei cookie tecnici può però compromettere alcune funzionalità del sito.',
    ],

    'terms' => [
        'title' => 'Termini d\'uso',
        'intro' => 'Utilizzando questo sito accetti i presenti termini d\'uso.',

        'service_t' => 'Il servizio',
        'service'   => 'Il sito offre gratuitamente la generazione di barcode in vari formati. Il servizio è fornito "così com\'è", senza garanzie di disponibilità continuativa, e può essere modificato o sospeso in qualsiasi momento.',

        'nowarranty_t' => 'Assenza di garanzie e responsabilità',
        'nowarranty'   => 'Pur impegnandosi per la correttezza dei codici generati, il titolare non fornisce alcuna garanzia sull\'idoneità a uno scopo specifico e non è responsabile per eventuali danni derivanti dall\'uso del servizio o dei codici prodotti. Spetta all\'utente verificare la validità e la correttezza dei barcode prima dell\'uso (ad es. in stampa o in produzione).',

        'use_t' => 'Uso corretto',
        'use'   => 'È vietato utilizzare il servizio per scopi illeciti, per generare codici che violino diritti di terzi o per sovraccaricare/abusare dell\'infrastruttura.',

        'law_t' => 'Legge applicabile',
        'law'   => 'I presenti termini sono regolati dalla legge italiana. Per quanto non previsto si rinvia alla normativa vigente.',
    ],
];
