<?php

namespace App\Http\Controllers;

use App\Models\Pipeline;
use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PipelineController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Pipeline::with('repository')->latest('run_at')->paginate(20));
    }

    public function show(Pipeline $pipeline): JsonResponse
    {
        return response()->json($pipeline->load('repository'));
    }

    public function byRepository(Repository $repository): JsonResponse
    {
        return response()->json($repository->pipelines()->latest('run_at')->get());
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

        return response()->json(Pipeline::create($data), 201);
    }

    public function destroy(Pipeline $pipeline): JsonResponse
    {
        $pipeline->delete();

        return response()->json(null, 204);
    }
}
