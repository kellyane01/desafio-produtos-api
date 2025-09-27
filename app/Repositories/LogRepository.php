<?php

namespace App\Repositories;

use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LogRepository implements LogRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Log::query();

        if (! empty($filters['model'])) {
            $query->where('model', $filters['model']);
        }

        if (! empty($filters['model_id'])) {
            $query->where('model_id', (int) $filters['model_id']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['from'])) {
            $from = Carbon::parse($filters['from'])->startOfDay();
            $query->where('created_at', '>=', $from);
        }

        if (! empty($filters['to'])) {
            $to = Carbon::parse($filters['to'])->endOfDay();
            $query->where('created_at', '<=', $to);
        }

        $query->orderByDesc('created_at');

        return $query->paginate($perPage);
    }
}
