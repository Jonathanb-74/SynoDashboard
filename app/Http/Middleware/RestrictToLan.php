<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictToLan
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw = config('access.allowed_cidrs', '');

        if (empty($raw)) {
            return $next($request);
        }

        $cidrs = array_filter(array_map('trim', explode(',', $raw)));
        $ip    = $request->ip();

        foreach ($cidrs as $cidr) {
            if ($this->matches($ip, $cidr)) {
                return $next($request);
            }
        }

        abort(403, 'Accès restreint au réseau local.');
    }

    private function matches(string $ip, string $cidr): bool
    {
        [$network, $prefix] = str_contains($cidr, '/') ? explode('/', $cidr, 2) : [$cidr, '32'];

        $ip      = ip2long($ip);
        $network = ip2long($network);

        if ($ip === false || $network === false) {
            return false;
        }

        $mask = $prefix < 32 ? ~((1 << (32 - (int) $prefix)) - 1) : -1;

        return ($ip & $mask) === ($network & $mask);
    }
}
