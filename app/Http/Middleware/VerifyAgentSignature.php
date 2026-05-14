<?php

namespace App\Http\Middleware;

use App\Models\ApiLog;
use App\Models\NasDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAgentSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $rawBody   = $request->getContent();
        $payload   = json_decode($rawBody, true);
        $serial    = $payload['nas_identifier']['serial'] ?? null;

        // No serial → let the controller return a proper 422
        if (!$serial) {
            return $next($request);
        }

        $nas = NasDevice::where('serial', $serial)->first();

        // Unknown NAS or no key configured yet → enrollment phase, pass through
        if (!$nas || !$nas->hmac_secret) {
            return $next($request);
        }

        // Key is configured: signature is mandatory
        $header   = $request->header('X-Agent-Signature', '');
        $received = str_starts_with($header, 'sha256=') ? substr($header, 7) : $header;
        $expected = hash_hmac('sha256', $rawBody, $nas->hmac_secret);

        if (!hash_equals($expected, $received)) {
            $response = response()->json([
                'status'  => 'error',
                'message' => 'Invalid signature.',
            ], 401);

            try {
                ApiLog::create([
                    'nas_id'      => $nas->id,
                    'nas_serial'  => $serial,
                    'ip_address'  => $request->ip(),
                    'http_method' => $request->method(),
                    'path'        => $request->path(),
                    'status_code' => 401,
                    'payload'     => $rawBody,
                    'response'    => $response->getContent(),
                    'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                    'error'       => 'Invalid HMAC signature',
                ]);
            } catch (\Throwable) {}

            return $response;
        }

        return $next($request);
    }
}
