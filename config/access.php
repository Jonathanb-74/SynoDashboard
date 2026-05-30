<?php

return [
    /*
     * Comma-separated CIDR ranges allowed to access the web UI.
     * Leave empty to disable the restriction (dev / local).
     * Example: "172.16.0.0/16,172.17.0.0/16,127.0.0.1/32"
     */
    'allowed_cidrs' => env('ADMIN_ALLOWED_CIDRS', ''),

    /*
     * Comma-separated CIDR ranges explicitly denied, even if they fall
     * inside an allowed range. Checked first — denies take priority.
     * Example: "172.17.5.10/32" to exclude a specific reverse-proxy IP.
     */
    'blocked_cidrs' => env('ADMIN_BLOCKED_CIDRS', ''),

    /*
     * Allow public self-registration at /register.
     * Set to false in production and use invitations instead.
     */
    'registration_enabled' => env('REGISTRATION_ENABLED', false),

    /*
     * Comma-separated IPs/CIDRs of trusted reverse proxies.
     * When set, $request->ip() resolves the real client IP from
     * the X-Forwarded-For header instead of the proxy's IP.
     * Use "*" to trust any proxy (only safe behind a controlled network).
     * Example: "172.17.5.10/32,172.18.0.0/16"
     */
    'trusted_proxies' => env('TRUSTED_PROXIES', ''),
];
