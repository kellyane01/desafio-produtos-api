<?php

namespace App\Search;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SearchHealthReporter
{
    private const STATUS_CACHE_KEY = 'search:elasticsearch:status';

    private const LAST_FAILURE_LOGGED_AT = 'search:elasticsearch:last_failure_logged_at';

    public function recordFailure(string $reason, array $context = []): void
    {
        $payload = [
            'status' => 'unhealthy',
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ];

        Cache::put(self::STATUS_CACHE_KEY, $payload, now()->addMinutes(30));

        if ($this->shouldLogFailure()) {
            Cache::put(self::LAST_FAILURE_LOGGED_AT, now()->timestamp, now()->addMinutes(5));
            Log::channel('search')->warning('Elasticsearch indisponível, usando fallback para banco relacional.', array_merge($context, $payload));
        }
    }

    public function recordSuccess(): void
    {
        $previous = Cache::get(self::STATUS_CACHE_KEY);

        $payload = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
        ];

        Cache::put(self::STATUS_CACHE_KEY, $payload, now()->addMinutes(30));

        if (($previous['status'] ?? null) === 'unhealthy') {
            Log::channel('search')->info('Elasticsearch voltou a responder.');
        }
    }

    private function shouldLogFailure(): bool
    {
        $lastLogged = Cache::get(self::LAST_FAILURE_LOGGED_AT);
        if ($lastLogged === null) {
            return true;
        }

        $elapsed = now()->timestamp - (int) $lastLogged;

        return $elapsed >= 300;
    }
}
