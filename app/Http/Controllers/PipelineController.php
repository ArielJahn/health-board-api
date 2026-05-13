<?php

namespace App\Http\Controllers;

use App\Models\Pipeline;
use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PipelineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'sometimes|in:success,failure,cancelled,in_progress',
        ]);

        $paginator = Pipeline::with('repository')
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->latest('run_at')
            ->paginate(20);

        return $this->paginated($paginator);
    }

    public function show(Pipeline $pipeline): JsonResponse
    {
        return $this->ok($pipeline->load('repository'));
    }

    public function byRepository(Request $request, Repository $repository): JsonResponse
    {
        $request->validate([
            'limit'  => 'sometimes|integer|min:1|max:100',
            'status' => 'sometimes|in:success,failure,cancelled,in_progress',
        ]);

        $limit = (int) $request->query('limit', 20);

        $pipelines = $repository->pipelines()
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->latest('run_at')
            ->limit($limit)
            ->get();

        return $this->ok($pipelines);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'repository_id' => 'required|exists:repositories,id',
            'workflow_name' => 'required|string|max:255',
            'status'        => 'required|in:success,failure,cancelled,in_progress',
            'branch'        => 'required|string|max:255',
            'duration'      => 'nullable|integer|min:0',
            'run_at'        => 'required|date',
        ]);

        return $this->created(Pipeline::create($data));
    }

    public function destroy(Pipeline $pipeline): JsonResponse
    {
        $pipeline->delete();

        return $this->noContent();
    }
}
