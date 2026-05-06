<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use Illuminate\Http\JsonResponse;

class HealthScoreController extends Controller
{
    public function show(Repository $repository): JsonResponse
    {
        $score = 0;
        $details = [];

        // CI success rate — últimos 10 runs (50 pontos)
        $recentPipelines = $repository->pipelines()->latest('run_at')->limit(10)->get();

        if ($recentPipelines->isNotEmpty()) {
            $successRate = $recentPipelines->where('status', 'success')->count() / $recentPipelines->count();
            $ciScore = (int) round($successRate * 50);
            $score += $ciScore;
            $details['ci'] = [
                'score'        => $ciScore,
                'max'          => 50,
                'success_rate' => round($successRate * 100, 1) . '%',
                'runs_checked' => $recentPipelines->count(),
            ];
        } else {
            $details['ci'] = ['score' => 0, 'max' => 50, 'note' => 'Sem pipelines registrados'];
        }

        // Último deploy < 7 dias (30 pontos)
        $lastRelease = $repository->releases()->latest('deployed_at')->first();

        if ($lastRelease && $lastRelease->deployed_at->diffInDays(now()) <= 7) {
            $score += 30;
            $details['deploy'] = [
                'score'       => 30,
                'max'         => 30,
                'last_deploy' => $lastRelease->deployed_at->toDateTimeString(),
            ];
        } else {
            $details['deploy'] = [
                'score' => 0,
                'max'   => 30,
                'note'  => $lastRelease ? 'Último deploy há mais de 7 dias' : 'Sem releases registradas',
            ];
        }

        // Zero incidentes abertos (20 pontos)
        $openIncidents = $repository->incidents()->where('status', '!=', 'resolved')->count();

        if ($openIncidents === 0) {
            $score += 20;
            $details['incidents'] = ['score' => 20, 'max' => 20, 'open' => 0];
        } else {
            $details['incidents'] = ['score' => 0, 'max' => 20, 'open' => $openIncidents];
        }

        return response()->json([
            'repository_id' => $repository->id,
            'repository'    => $repository->full_name,
            'score'         => $score,
            'max_score'     => 100,
            'status'        => $this->statusLabel($score),
            'details'       => $details,
        ]);
    }

    private function statusLabel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'healthy',
            $score >= 50 => 'degraded',
            default      => 'critical',
        };
    }
}
