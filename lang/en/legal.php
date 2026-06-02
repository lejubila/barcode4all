<?php

return [
    'updated'    => 'Last updated: :date',
    'no_date'    => 'to be defined',
    'home'       => 'Back to home',

    'privacy' => [
        'title' => 'Privacy Policy',
        'intro' => 'This policy describes how the personal data of users who browse and use this website is processed, pursuant to Articles 13 and 14 of Regulation (EU) 2016/679 (GDPR).',

        'controller_t' => 'Data controller',
        'controller'   => 'The data controller is :name. For any request regarding your data you can write to :email.',

        'data_t' => 'Data processed',
        'data'   => 'Browsing data: to operate the website, the IP address and technical connection data are processed (in server logs and for rate limiting for security purposes). Data entered to generate barcodes: the codes and any uploaded CSV files are used solely to produce the requested result; temporary files are deleted immediately after download and are not retained. Technical cookies: see the Cookie policy.',

        'purpose_t' => 'Purpose and legal basis',
        'purpose'   => 'Data is processed to provide the requested service (generating and downloading barcodes) and to ensure the security and proper functioning of the website. The legal basis is the performance of the service requested by the user and the controller\'s legitimate interest in protecting the infrastructure. Technical cookies are necessary and do not require consent.',

        'retention_t' => 'Retention',
        'retention'   => 'Browsing data and logs are kept only for as long as strictly necessary for security purposes and then deleted or anonymised. Submitted codes and uploaded files are not retained: they are processed temporarily and removed once the operation is complete. The session has a limited lifetime.',

        'recipients_t' => 'Recipients and place of processing',
        'recipients'   => 'The website is hosted on :hosting, acting as data processor on behalf of the controller. Data is processed within the European Union and is not transferred to non-EU countries. Data is not disclosed to third parties nor used for purposes other than those stated.',

        'rights_t' => 'Your rights',
        'rights'   => 'You have the right to access your data and to request its rectification, erasure, restriction or to object to processing, as well as the right to data portability, under Articles 15-22 GDPR. You can exercise these rights by writing to :email. You also have the right to lodge a complaint with the Italian Data Protection Authority (www.garanteprivacy.it).',

        'profiling_t' => 'No profiling',
        'profiling'   => 'The website performs no profiling or automated decision-making and uses no analytics or tracking tools.',
    ],

    'cookie' => [
        'title' => 'Cookie Policy',
        'intro' => 'This website uses only technical cookies that are necessary for it to function. No profiling, analytics or third-party cookies are used; therefore no prior consent is required.',

        'tech_t'    => 'Technical cookies used',
        'c_session' => 'laravel_session — keeps the browsing session (e.g. the selected language). Duration: the session; first-party cookie.',
        'c_xsrf'    => 'XSRF-TOKEN — protects forms against CSRF attacks. Duration: the session; first-party cookie.',

        'noconsent' => 'Since these are technical cookies, no consent banner is required under the Data Protection Authority guidelines: this page fulfils the disclosure obligation.',

        'manage_t' => 'Managing cookies',
        'manage'   => 'You can delete or block cookies from your browser settings; disabling technical cookies may, however, impair some features of the website.',
    ],

    'terms' => [
        'title' => 'Terms of Use',
        'intro' => 'By using this website you accept these terms of use.',

        'service_t' => 'The service',
        'service'   => 'The website offers free barcode generation in various formats. The service is provided "as is", with no guarantee of continuous availability, and may be changed or suspended at any time.',

        'nowarranty_t' => 'No warranty and liability',
        'nowarranty'   => 'While care is taken to ensure the correctness of the generated codes, the controller provides no warranty of fitness for a particular purpose and is not liable for any damage arising from the use of the service or of the codes produced. It is the user\'s responsibility to verify the validity and correctness of the barcodes before use (e.g. in print or production).',

        'use_t' => 'Acceptable use',
        'use'   => 'You may not use the service for unlawful purposes, to generate codes that infringe third-party rights, or to overload/abuse the infrastructure.',

        'law_t' => 'Governing law',
        'law'   => 'These terms are governed by Italian law. For anything not covered here, the applicable legislation applies.',
    ],
];
