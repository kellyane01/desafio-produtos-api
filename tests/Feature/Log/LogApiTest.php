<?php

namespace Tests\Feature\Log;

use App\Models\Log;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/logs')->assertUnauthorized();
    }

    public function test_logs_endpoint_returns_filtered_results(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-01-01 09:00:00'));
        $baseline = Log::factory()->create([
            'model' => 'App\\Models\\Produto',
            'action' => 'create',
            'model_id' => 10,
        ]);

        Carbon::setTestNow(Carbon::parse('2024-01-10 12:00:00'));
        $expected = Log::factory()->create([
            'model' => 'App\\Models\\Produto',
            'action' => 'update',
            'model_id' => 10,
            'user_id' => 5,
        ]);

        Carbon::setTestNow(Carbon::parse('2024-01-15 08:00:00'));
        Log::factory()->create([
            'model' => 'App\\Models\\Pedido',
            'action' => 'delete',
            'model_id' => 99,
        ]);

        Carbon::setTestNow();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/logs?'.http_build_query([
            'model' => 'App\\Models\\Produto',
            'model_id' => 10,
            'action' => 'update',
            'from' => '2024-01-05',
            'to' => '2024-01-12',
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $expected->id,
                'action' => 'update',
                'model' => 'App\\Models\\Produto',
                'model_id' => 10,
            ])
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'action',
                    'model',
                    'model_id',
                    'data',
                    'user_id',
                    'created_at',
                    'updated_at',
                ]],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertSame(
            Carbon::parse('2024-01-10 12:00:00')->toIso8601String(),
            $response->json('data.0.created_at')
        );
        $this->assertSame($expected->id, $response->json('data.0.id'));
        $this->assertNotSame($baseline->id, $response->json('data.0.id'));
    }
}
