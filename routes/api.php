<?php

use App\Http\Controllers\HealthScoreController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\ReleaseController;
use App\Http\Controllers\RepositoryController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Webhook — sem autenticação (GitHub envia diretamente)
Route::post('/webhooks/github', [WebhookController::class, 'github']);

// Rotas protegidas por API token (Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // Repositories
    Route::apiResource('repositories', RepositoryController::class);

    // Pipelines
    Route::get('/pipelines', [PipelineController::class, 'index']);
    Route::get('/pipelines/{pipeline}', [PipelineController::class, 'show']);
    Route::post('/pipelines', [PipelineController::class, 'store']);
    Route::delete('/pipelines/{pipeline}', [PipelineController::class, 'destroy']);
    Route::get('/repositories/{repository}/pipelines', [PipelineController::class, 'byRepository']);

    // Releases
    Route::get('/releases', [ReleaseController::class, 'index']);
    Route::get('/releases/{release}', [ReleaseController::class, 'show']);
    Route::post('/releases', [ReleaseController::class, 'store']);
    Route::delete('/releases/{release}', [ReleaseController::class, 'destroy']);
    Route::get('/repositories/{repository}/releases', [ReleaseController::class, 'byRepository']);

    // Incidents
    Route::get('/repositories/{repository}/incidents', [IncidentController::class, 'byRepository']);
    Route::get('/incidents', [IncidentController::class, 'index']);
    Route::get('/incidents/open', [IncidentController::class, 'open']);
    Route::get('/incidents/{incident}', [IncidentController::class, 'show']);
    Route::post('/incidents', [IncidentController::class, 'store']);
    Route::put('/incidents/{incident}', [IncidentController::class, 'update']);
    Route::delete('/incidents/{incident}', [IncidentController::class, 'destroy']);

    // Health Score
    Route::get('/health-scores', [HealthScoreController::class, 'index']);
    Route::get('/health-score/{repository}', [HealthScoreController::class, 'show']);
});
