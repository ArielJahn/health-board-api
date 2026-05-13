<?php

namespace App\Http\Controllers;

use App\Models\Release;
use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReleaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'environment' => 'sometimes|in:dev,staging,production',
        ]);

        $paginator = Release::with('repository')
            ->when($request->environment, fn ($q, $v) => $q->where('environment', $v))
            ->latest('deployed_at')
            ->paginate(20);

        return $this->paginated($paginator);
    }

    public function show(Release $release): JsonResponse
    {
        return $this->ok($release->load('repository'));
    }

    public function byRepository(Request $request, Repository $repository): JsonResponse
    {
        $request->validate([
            'limit'       => 'sometimes|integer|min:1|max:100',
            'environment' => 'sometimes|in:dev,staging,production',
        ]);

        $limit = (int) $request->query('limit', 20);

        $releases = $repository->releases()
            ->when($request->environment, fn ($q, $v) => $q->where('environment', $v))
            ->latest('deployed_at')
            ->limit($limit)
            ->get();

        return $this->ok($releases);
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

        return $this->created(Release::create($data));
    }

    public function destroy(Release $release): JsonResponse
    {
        $release->delete();

        return $this->noContent();
    }
}
