<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Incident::with('repository')->latest('opened_at')->get());
    }

    public function show(Incident $incident): JsonResponse
    {
        return response()->json($incident->load('repository'));
    }

    public function open(): JsonResponse
    {
        return response()->json(
            Incident::with('repository')->where('status', '!=', 'resolved')->latest('opened_at')->get()
        );
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

        return response()->json(Incident::create($data), 201);
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

        return response()->json($incident);
    }

    public function destroy(Incident $incident): JsonResponse
    {
        $incident->delete();

        return response()->json(null, 204);
    }
}
