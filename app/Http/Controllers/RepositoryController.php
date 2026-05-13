<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepositoryController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->paginated(Repository::paginate(20));
    }

    public function show(Repository $repository): JsonResponse
    {
        return $this->ok($repository->load(['pipelines', 'releases', 'incidents']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'full_name'    => 'required|string|max:255|unique:repositories',
            'github_url'   => 'required|url|max:500',
            'access_token' => 'nullable|string|max:255',
        ]);

        return $this->created(Repository::create($data));
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

        return $this->ok($repository);
    }

    public function destroy(Repository $repository): JsonResponse
    {
        $repository->delete();

        return $this->noContent();
    }
}
