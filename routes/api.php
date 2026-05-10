<?php

use App\Http\Controllers\Api\AgentApiController;
use App\Http\Middleware\VerifyAgentSignature;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/agent/ingest', [AgentApiController::class, 'ingest'])
        ->middleware(VerifyAgentSignature::class)
        ->name('api.agent.ingest');
});
