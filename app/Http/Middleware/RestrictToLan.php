<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictToLan
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = $this->parseCidrs(config('access.allowed_cidrs', ''));

        // No allowlist configured → open (dev/local)
        if (empty($allowed)) {
            return $next($request);
        }

        $ip = $request->ip();

        // Explicit denies take priority over the allowlist
        $blocked = $this->parseCidrs(config('access.blocked_cidrs', ''));
        foreach ($blocked as $cidr) {
            if ($this->matches($ip, $cidr)) {
                abort(403, 'Accès restreint.');
            }
        }

        foreach ($allowed as $cidr) {
            if ($this->matches($ip, $cidr)) {
                return $next($request);
            }
        }

        abort(403, 'Accès restreint au réseau local.');
    }

    private function parseCidrs(string $raw): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    private function matches(string $ip, string $cidr): bool
    {
        [$network, $prefix] = str_contains($cidr, '/') ? explode('/', $cidr, 2) : [$cidr, '32'];

        $ipLong  = ip2long($ip);
        $netLong = ip2long($network);

        if ($ipLong === false || $netLong === false) {
            return false;
        }

        $mask = $prefix < 32 ? ~((1 << (32 - (int) $prefix)) - 1) : -1;

        return ($ipLong & $mask) === ($netLong & $mask);
    }
}
