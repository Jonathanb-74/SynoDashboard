<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Services\IngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentApiController extends Controller
{
    public function __construct(private IngestionService $ingestionService) {}

    public function ingest(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $nasSerial = null;
        $nasId     = null;

        $payload = $request->json()->all();

        if (empty($payload)) {
            $response = response()->json(['status' => 'error', 'message' => 'Empty or invalid JSON body.'], 422);
            $this->log($request, $response, $startTime, null, null);
            return $response;
        }

        $nasSerial = $payload['nas_identifier']['serial'] ?? null;

        try {
            $result  = $this->ingestionService->ingest($payload);
            $nasId   = $result['nas']->id;

            $response = response()->json([
                'status'            => 'ok',
                'nas_id'            => $result['nas']->id,
                'snapshot_id'       => $result['snapshot']->id,
                'is_new'            => $result['is_new'],
                'collection_config' => $result['collection_config'],
            ]);

            $this->log($request, $response, $startTime, $nasSerial, $nasId);
            return $response;

        } catch (\InvalidArgumentException $e) {
            $response = response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            $this->log($request, $response, $startTime, $nasSerial, $nasId, $e->getMessage());
            return $response;

        } catch (\Throwable $e) {
            $response = response()->json(['status' => 'error', 'message' => 'Internal server error.'], 500);
            $this->log($request, $response, $startTime, $nasSerial, $nasId, $e->getMessage());
            return $response;
        }
    }

    private function log(
        Request      $request,
        JsonResponse $response,
        float        $startTime,
        ?string      $nasSerial,
        ?int         $nasId,
        ?string      $error = null
    ): void {
        try {
            ApiLog::create([
                'nas_id'         => $nasId,
                'nas_serial'     => $nasSerial,
                'ip_address'     => $request->ip(),
                'http_method'    => $request->method(),
                'path'           => $request->path(),
                'status_code'    => $response->getStatusCode(),
                'payload'        => $request->getContent(),
                'response'       => $response->getContent(),
                'duration_ms'    => (int) round((microtime(true) - $startTime) * 1000),
                'error'          => $error,
                'hmac_signature' => $request->header('X-Agent-Signature'),
            ]);
        } catch (\Throwable) {
            // Logging must never break the API response
        }
    }
}
