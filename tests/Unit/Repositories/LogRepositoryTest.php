<?php

namespace Tests\Unit\Repositories;

use App\Models\Log;
use App\Repositories\LogRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginate_applies_all_supported_filters(): void
    {
        Carbon::setTestNow('2024-02-01 10:00:00');
        $included = Log::factory()->create([
            'model' => 'App\\Models\\Produto',
            'model_id' => 5,
            'action' => 'update',
            'user_id' => 7,
            'created_at' => Carbon::parse('2024-01-05 09:00:00'),
        ]);

        Log::factory()->create([
            'model' => 'App\\Models\\Produto',
            'model_id' => 5,
            'action' => 'create',
            'user_id' => 7,
            'created_at' => Carbon::parse('2024-01-05 09:00:00'),
        ]);

        Log::factory()->create([
            'model' => 'App\\Models\\Pedido',
            'model_id' => 5,
            'action' => 'update',
            'user_id' => 7,
            'created_at' => Carbon::parse('2024-01-05 09:00:00'),
        ]);

        Log::factory()->create([
            'model' => 'App\\Models\\Produto',
            'model_id' => 5,
            'action' => 'update',
            'user_id' => 8,
            'created_at' => Carbon::parse('2024-01-20 09:00:00'),
        ]);

        $repository = app(LogRepositoryInterface::class);

        $result = $repository->paginate([
            'model' => 'App\\Models\\Produto',
            'model_id' => 5,
            'action' => 'update',
            'user_id' => 7,
            'from' => '2024-01-01',
            'to' => '2024-01-10',
        ], 15);

        $this->assertSame(1, $result->total());
        $this->assertSame($included->id, $result->items()[0]->id);
    }
}
