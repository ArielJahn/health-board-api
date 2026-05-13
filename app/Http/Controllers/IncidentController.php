<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status'   => 'sometimes|in:open,investigating,resolved',
            'severity' => 'sometimes|in:low,medium,high,critical',
        ]);

        $paginator = Incident::with('repository')
            ->when($request->status,   fn ($q, $v) => $q->where('status', $v))
            ->when($request->severity, fn ($q, $v) => $q->where('severity', $v))
            ->latest('opened_at')
            ->paginate(20);

        return $this->paginated($paginator);
    }

    public function show(Incident $incident): JsonResponse
    {
        return $this->ok($incident->load('repository'));
    }

    public function open(): JsonResponse
    {
        return $this->ok(
            Incident::with('repository')
                ->whereIn('status', ['open', 'investigating'])
                ->latest('opened_at')
                ->get()
        );
    }

    public function byRepository(Request $request, Repository $repository): JsonResponse
    {
        $request->validate([
            'status'   => 'sometimes|in:open,investigating,resolved',
            'severity' => 'sometimes|in:low,medium,high,critical',
            'limit'    => 'sometimes|integer|min:1|max:100',
        ]);

        $limit = (int) $request->query('limit', 20);

        $incidents = $repository->incidents()
            ->when($request->status,   fn ($q, $v) => $q->where('status', $v))
            ->when($request->severity, fn ($q, $v) => $q->where('severity', $v))
            ->latest('opened_at')
            ->limit($limit)
            ->get();

        return $this->ok($incidents);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'repository_id' => 'required|exists:repositories,id',
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'severity'      => 'required|in:low,medium,high,critical',
            'status'        => 'sometimes|in:open,investigating,resolved',
            'opened_at'     => 'required|date',
            'resolved_at'   => 'nullable|date|after_or_equal:opened_at',
        ]);

        return $this->created(Incident::create($data));
    }

    public function update(Request $request, Incident $incident): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'severity'    => 'sometimes|in:low,medium,high,critical',
            'status'      => 'sometimes|in:open,investigating,resolved',
            'resolved_at' => 'nullable|date|after_or_equal:opened_at',
        ]);

        if (isset($data['status']) && $data['status'] === 'resolved' && ! $incident->resolved_at) {
            $data['resolved_at'] = now();
        }

        $incident->update($data);

        return $this->ok($incident);
    }

    public function destroy(Incident $incident): JsonResponse
    {
        $incident->delete();

        return $this->noContent();
    }
}
