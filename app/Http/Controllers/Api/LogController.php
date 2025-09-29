<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Log\ListLogRequest;
use App\Http\Resources\LogResource;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;

class LogController extends Controller
{
    public function __construct(private readonly LogService $service) {}

    public function index(ListLogRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = collect($validated)
            ->except(['per_page'])
            ->filter(static fn ($value) => $value !== null && $value !== '')
            ->all();

        $perPage = (int) ($validated['per_page'] ?? $request->query('per_page', 15));
        if ($perPage <= 0) {
            $perPage = 15;
        }

        $logs = $this->service->list($filters, $perPage);

        return LogResource::collection($logs)->response();
    }
}
