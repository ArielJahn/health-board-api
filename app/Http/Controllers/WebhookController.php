<?php

namespace App\Http\Controllers;

use App\Models\Pipeline;
use App\Models\Release;
use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function github(Request $request): JsonResponse
    {
        $event = $request->header('X-GitHub-Event');
        $payload = $request->json()->all();

        $repoFullName = $payload['repository']['full_name'] ?? null;

        if (! $repoFullName) {
            return response()->json(['error' => 'Payload inválido'], 400);
        }

        $repository = Repository::where('full_name', $repoFullName)->first();

        if (! $repository) {
            return response()->json(['error' => 'Repositório não monitorado'], 404);
        }

        return match ($event) {
            'workflow_run' => $this->handleWorkflowRun($repository, $payload),
            'release'      => $this->handleRelease($repository, $payload),
            default        => response()->json(['message' => "Evento '{$event}' ignorado"], 200),
        };
    }

    private function handleWorkflowRun(Repository $repository, array $payload): JsonResponse
    {
        $run = $payload['workflow_run'];

        $status = match ($run['conclusion'] ?? $run['status']) {
            'success'   => 'success',
            'failure'   => 'failure',
            'cancelled' => 'cancelled',
            default     => 'in_progress',
        };

        $duration = null;
        if ($run['run_started_at'] && $run['updated_at']) {
            $start    = new \DateTime($run['run_started_at']);
            $end      = new \DateTime($run['updated_at']);
            $duration = $end->getTimestamp() - $start->getTimestamp();
        }

        $pipeline = Pipeline::create([
            'repository_id' => $repository->id,
            'workflow_name' => $run['name'],
            'status'        => $status,
            'branch'        => $run['head_branch'],
            'duration'      => $duration,
            'run_at'        => $run['run_started_at'],
        ]);

        return response()->json(['message' => 'Pipeline registrado', 'pipeline_id' => $pipeline->id], 201);
    }

    private function handleRelease(Repository $repository, array $payload): JsonResponse
    {
        $rel = $payload['release'];

        $release = Release::create([
            'repository_id' => $repository->id,
            'version'       => $rel['tag_name'],
            'environment'   => 'production',
            'deployed_at'   => $rel['published_at'],
            'changelog'     => $rel['body'] ?? null,
        ]);

        return response()->json(['message' => 'Release registrada', 'release_id' => $release->id], 201);
    }
}
