<?php

namespace App\Jobs;

use App\Models\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogModelActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int|string, mixed>|null  $data
     */
    public function __construct(
        private readonly string $action,
        private readonly string $model,
        private readonly int $modelId,
        private readonly ?array $data = null,
        private readonly ?int $userId = null,
    ) {}

    public function handle(): void
    {
        Log::create([
            'action' => $this->action,
            'model' => $this->model,
            'model_id' => $this->modelId,
            'data' => $this->normalize($this->data),
            'user_id' => $this->userId,
        ]);
    }

    /**
     * @param  array<int|string, mixed>|null  $data
     */
    private function normalize(?array $data): ?array
    {
        return empty($data) ? null : $data;
    }
}
