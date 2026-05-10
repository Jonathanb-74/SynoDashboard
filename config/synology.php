<?php

return [
    'timeout'            => env('SYNOLOGY_TIMEOUT', 30),
    'default_ssl_verify' => env('SYNOLOGY_SSL_VERIFY', true),
    'session_name'       => 'SynoManager',
    'standard_apis'      => [
        'SYNO.Core.System',
        'SYNO.Core.Network',
        'SYNO.Storage.CGI.Storage',
        'SYNO.Core.Package',
        'SYNO.Core.Upgrade',
    ],
];
