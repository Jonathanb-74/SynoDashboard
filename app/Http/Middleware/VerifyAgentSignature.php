<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAgentSignature
{
    // TODO: implement HMAC-SHA256 signature verification
    // The future agent will sign payloads with its private key.
    // Verification: compute HMAC-SHA256 of request body using the NAS public key
    // and compare with the X-Agent-Signature header value.

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
