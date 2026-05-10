<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentApiController extends Controller
{
    public function __construct(private IngestionService $ingestionService) {}

    public function ingest(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        if (empty($payload)) {
            return response()->json(['status' => 'error', 'message' => 'Empty or invalid JSON body.'], 422);
        }

        try {
            $result = $this->ingestionService->ingest($payload);

            return response()->json([
                'status'      => 'ok',
                'nas_id'      => $result['nas']->id,
                'snapshot_id' => $result['snapshot']->id,
                'is_new'      => $result['is_new'],
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);

        } catch (\Throwable) {
            return response()->json(['status' => 'error', 'message' => 'Internal server error.'], 500);
        }
    }
}
