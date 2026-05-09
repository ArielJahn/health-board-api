<?php

namespace App\Http\Controllers;

use App\Models\Release;
use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReleaseController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Release::with('repository')->latest('deployed_at')->paginate(20));
    }

    public function show(Release $release): JsonResponse
    {
        return response()->json($release->load('repository'));
    }

    public function byRepository(Repository $repository): JsonResponse
    {
        return response()->json($repository->releases()->latest('deployed_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'repository_id' => 'required|exists:repositories,id',
            'version'       => 'required|string|max:50',
            'environment'   => 'required|in:dev,staging,production',
            'deployed_at'   => 'required|date',
            'changelog'     => 'nullable|string',
        ]);

        return response()->json(Release::create($data), 201);
    }

    public function destroy(Release $release): JsonResponse
    {
        $release->delete();

        return response()->json(null, 204);
    }
}
