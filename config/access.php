<?php

return [
    /*
     * Comma-separated list of CIDR ranges allowed to access the web UI.
     * Leave empty to disable the restriction (dev / local).
     * Example: "172.16.0.0/16,172.17.0.0/16,127.0.0.1/32"
     */
    'allowed_cidrs' => env('ADMIN_ALLOWED_CIDRS', ''),
];
