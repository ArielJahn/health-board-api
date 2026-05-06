<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepositoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Repository::all());
    }

    public function show(Repository $repository): JsonResponse
    {
        return response()->json($repository->load(['pipelines', 'releases', 'incidents']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'full_name'    => 'required|string|max:255|unique:repositories',
            'github_url'   => 'required|url|max:500',
            'access_token' => 'nullable|string|max:255',
        ]);

        return response()->json(Repository::create($data), 201);
    }

    public function update(Request $request, Repository $repository): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'full_name'    => 'sometimes|string|max:255|unique:repositories,full_name,' . $repository->id,
            'github_url'   => 'sometimes|url|max:500',
            'access_token' => 'nullable|string|max:255',
        ]);

        $repository->update($data);

        return response()->json($repository);
    }

    public function destroy(Repository $repository): JsonResponse
    {
        $repository->delete();

        return response()->json(null, 204);
    }
}
