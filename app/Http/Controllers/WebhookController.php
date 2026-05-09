<?php

namespace App\Http\Controllers;

use App\Models\Pipeline;
use App\Models\Release;
use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function github(Request $request): JsonResponse
    {
        if (! $this->signatureIsValid($request)) {
            return response()->json(['error' => 'Assinatura inválida'], 401);
        }

        $event   = $request->header('X-GitHub-Event');
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
            'push'         => $this->handlePush($repository, $payload),
            'workflow_run' => $this->handleWorkflowRun($repository, $payload),
            'release'      => $this->handleRelease($repository, $payload),
            default        => response()->json(['message' => "Evento '{$event}' ignorado"], 200),
        };
    }

    private function signatureIsValid(Request $request): bool
    {
        $secret = config('services.github.webhook_secret');

        // Se não houver secret configurado, ignora verificação (ambiente local sem secret)
        if (empty($secret)) {
            return true;
        }

        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    private function handlePush(Repository $repository, array $payload): JsonResponse
    {
        $branch = str_replace('refs/heads/', '', $payload['ref'] ?? '');

        // Registra push apenas em branches principais
        if (! in_array($branch, ['main', 'master', 'develop'])) {
            return response()->json(['message' => "Push em '{$branch}' ignorado"], 200);
        }

        $pipeline = Pipeline::create([
            'repository_id' => $repository->id,
            'workflow_name' => 'push',
            'status'        => 'in_progress',
            'branch'        => $branch,
            'duration'      => null,
            'run_at'        => now(),
        ]);

        return response()->json([
            'message'     => 'Push registrado',
            'pipeline_id' => $pipeline->id,
            'branch'      => $branch,
        ], 201);
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
        if (! empty($run['run_started_at']) && ! empty($run['updated_at'])) {
            $start    = new \DateTime($run['run_started_at']);
            $end      = new \DateTime($run['updated_at']);
            $duration = max(0, $end->getTimestamp() - $start->getTimestamp());
        }

        $pipeline = Pipeline::create([
            'repository_id' => $repository->id,
            'workflow_name' => $run['name'],
            'status'        => $status,
            'branch'        => $run['head_branch'],
            'duration'      => $duration,
            'run_at'        => $run['run_started_at'],
        ]);

        return response()->json([
            'message'     => 'Pipeline registrado',
            'pipeline_id' => $pipeline->id,
            'status'      => $status,
        ], 201);
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

        return response()->json([
            'message'    => 'Release registrada',
            'release_id' => $release->id,
            'version'    => $rel['tag_name'],
        ], 201);
    }
}
