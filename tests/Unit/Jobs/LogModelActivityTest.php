<?php

namespace Tests\Unit\Jobs;

use App\Jobs\LogModelActivity;
use App\Models\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogModelActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_persists_log_with_payload(): void
    {
        $job = new LogModelActivity(
            action: 'create',
            model: 'Produto',
            modelId: 321,
            data: ['nome' => 'Produto Especial'],
            userId: 99,
        );

        $job->handle();

        $log = Log::first();

        $this->assertNotNull($log);
        $this->assertSame('create', $log->action);
        $this->assertSame('Produto', $log->model);
        $this->assertSame(321, $log->model_id);
        $this->assertSame(['nome' => 'Produto Especial'], $log->data);
        $this->assertSame(99, $log->user_id);
    }

    public function test_handle_normalizes_empty_payload_to_null(): void
    {
        $job = new LogModelActivity(
            action: 'delete',
            model: 'Produto',
            modelId: 654,
            data: [],
            userId: null,
        );

        $job->handle();

        $log = Log::first();

        $this->assertSame('delete', $log->action);
        $this->assertNull($log->data);
    }
}
