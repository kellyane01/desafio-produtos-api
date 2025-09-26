<?php

namespace Tests\Unit\Observers;

use App\Jobs\LogModelActivity;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProdutoObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_created_dispatches_log_job_with_expected_payload(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        Auth::login($user);

        $produto = Produto::factory()->create([
            'nome' => 'Notebook Pro',
            'descricao' => 'Modelo voltado para profissionais',
        ]);

        Bus::assertDispatchedTimes(LogModelActivity::class, 1);

        Bus::assertDispatched(LogModelActivity::class, function (LogModelActivity $job) use ($produto, $user) {
            $payload = $this->extractPayload($job);

            $this->assertSame('create', $payload['action']);
            $this->assertSame('Produto', $payload['model']);
            $this->assertSame($produto->getKey(), $payload['modelId']);
            $this->assertSame($user->getKey(), $payload['userId']);
            $this->assertEquals($produto->nome, $payload['data']['nome']);
            $this->assertEquals($produto->descricao, $payload['data']['descricao']);

            return true;
        });

        Auth::logout();
    }

    public function test_updated_dispatches_log_job_with_changed_attributes(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $produto = Produto::withoutEvents(fn () => Produto::factory()->create([
            'nome' => 'Teclado Compacto',
            'estoque' => 4,
        ]));

        Bus::fake();

        $produto->update([
            'nome' => 'Teclado Mecanico',
            'estoque' => 10,
        ]);

        Bus::assertDispatchedTimes(LogModelActivity::class, 1);

        Bus::assertDispatched(LogModelActivity::class, function (LogModelActivity $job) use ($produto, $user) {
            $payload = $this->extractPayload($job);

            $this->assertSame('update', $payload['action']);
            $this->assertSame($produto->getKey(), $payload['modelId']);
            $this->assertSame($user->getKey(), $payload['userId']);
            $this->assertEquals('Teclado Mecanico', $payload['data']['nome']);
            $this->assertEquals(10, (int) $payload['data']['estoque']);

            return true;
        });

        Auth::logout();
    }

    public function test_deleted_dispatches_log_job_with_original_attributes(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $produto = Produto::withoutEvents(fn () => Produto::factory()->create([
            'nome' => 'Mouse Gamer',
            'estoque' => 7,
        ]));

        Bus::fake();

        $produto->delete();

        Bus::assertDispatchedTimes(LogModelActivity::class, 1);

        Bus::assertDispatched(LogModelActivity::class, function (LogModelActivity $job) use ($produto, $user) {
            $payload = $this->extractPayload($job);

            $this->assertSame('delete', $payload['action']);
            $this->assertSame($produto->getKey(), $payload['modelId']);
            $this->assertSame($user->getKey(), $payload['userId']);
            $this->assertEquals('Mouse Gamer', $payload['data']['nome']);
            $this->assertEquals(7, (int) $payload['data']['estoque']);

            return true;
        });

        Auth::logout();
    }

    /**
     * @return array{action: string, model: string, modelId: int, data: ?array, userId: ?int}
     */
    private function extractPayload(LogModelActivity $job): array
    {
        $closure = function () {
            return [
                'action' => $this->action,
                'model' => $this->model,
                'modelId' => $this->modelId,
                'data' => $this->data,
                'userId' => $this->userId,
            ];
        };

        /** @var array{action: string, model: string, modelId: int, data: ?array, userId: ?int} $payload */
        $payload = $closure->call($job);

        return $payload;
    }
}
