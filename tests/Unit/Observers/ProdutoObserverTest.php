<?php

namespace Tests\Unit\Observers;

use App\Jobs\LogModelActivity;
use App\Jobs\SyncProdutoSearchDocument;
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
        Bus::assertDispatchedTimes(SyncProdutoSearchDocument::class, 1);

        Bus::assertDispatched(LogModelActivity::class, function (LogModelActivity $job) use ($produto, $user) {
            $payload = $this->extractPayload($job);

            $this->assertSame('create', $payload['action']);
            $this->assertSame(Produto::class, $payload['model']);
            $this->assertSame($produto->getKey(), $payload['modelId']);
            $this->assertSame($user->getKey(), $payload['userId']);
            $this->assertEquals($produto->nome, $payload['data']['after']['nome']);
            $this->assertEquals($produto->descricao, $payload['data']['after']['descricao']);

            return true;
        });

        Bus::assertDispatched(SyncProdutoSearchDocument::class, function (SyncProdutoSearchDocument $job) use ($produto) {
            $payload = $this->extractSyncPayload($job);

            $this->assertSame($produto->getKey(), $payload['produtoId']);
            $this->assertSame(SyncProdutoSearchDocument::OPERATION_UPSERT, $payload['operation']);
            $this->assertSame('search-sync', $payload['queue']);

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
        Bus::assertDispatchedTimes(SyncProdutoSearchDocument::class, 1);

        Bus::assertDispatched(LogModelActivity::class, function (LogModelActivity $job) use ($produto, $user) {
            $payload = $this->extractPayload($job);

            $this->assertSame('update', $payload['action']);
            $this->assertSame($produto->getKey(), $payload['modelId']);
            $this->assertSame($user->getKey(), $payload['userId']);
            $data = $payload['data'];
            $this->assertSame('Teclado Compacto', $data['before']['nome']);
            $this->assertSame('Teclado Mecanico', $data['after']['nome']);
            $this->assertSame(4, (int) $data['before']['estoque']);
            $this->assertSame(10, (int) $data['after']['estoque']);
            $this->assertSame(['old' => 'Teclado Compacto', 'new' => 'Teclado Mecanico'], $data['changes']['nome']);
            $this->assertSame(4, (int) $data['changes']['estoque']['old']);
            $this->assertSame(10, (int) $data['changes']['estoque']['new']);

            return true;
        });

        Bus::assertDispatched(SyncProdutoSearchDocument::class, function (SyncProdutoSearchDocument $job) use ($produto) {
            $payload = $this->extractSyncPayload($job);

            $this->assertSame($produto->getKey(), $payload['produtoId']);
            $this->assertSame(SyncProdutoSearchDocument::OPERATION_UPSERT, $payload['operation']);

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
        Bus::assertDispatchedTimes(SyncProdutoSearchDocument::class, 1);

        Bus::assertDispatched(LogModelActivity::class, function (LogModelActivity $job) use ($produto, $user) {
            $payload = $this->extractPayload($job);

            $this->assertSame('delete', $payload['action']);
            $this->assertSame($produto->getKey(), $payload['modelId']);
            $this->assertSame($user->getKey(), $payload['userId']);
            $data = $payload['data'];
            $this->assertSame('Mouse Gamer', $data['before']['nome']);
            $this->assertSame(7, (int) $data['before']['estoque']);
            $this->assertArrayNotHasKey('after', $data);
            $this->assertArrayNotHasKey('changes', $data);

            return true;
        });

        Bus::assertDispatched(SyncProdutoSearchDocument::class, function (SyncProdutoSearchDocument $job) use ($produto) {
            $payload = $this->extractSyncPayload($job);

            $this->assertSame($produto->getKey(), $payload['produtoId']);
            $this->assertSame(SyncProdutoSearchDocument::OPERATION_DELETE, $payload['operation']);

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

    /**
     * @return array{produtoId: int, operation: string, queue: ?string}
     */
    private function extractSyncPayload(SyncProdutoSearchDocument $job): array
    {
        $closure = function () {
            return [
                'produtoId' => $this->produtoId,
                'operation' => $this->operation,
                'queue' => $this->queue,
            ];
        };

        /** @var array{produtoId: int, operation: string, queue: ?string} $payload */
        $payload = $closure->call($job);

        return $payload;
    }
}
