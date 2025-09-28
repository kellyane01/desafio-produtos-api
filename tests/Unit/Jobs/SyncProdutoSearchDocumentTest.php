<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SyncProdutoSearchDocument;
use App\Models\Produto;
use App\Search\ProdutoIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class SyncProdutoSearchDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    public function test_dispatch_upsert_places_job_on_search_queue(): void
    {
        Bus::fake();

        $produto = Produto::withoutEvents(fn () => Produto::factory()->create());

        SyncProdutoSearchDocument::dispatchUpsert($produto);

        Bus::assertDispatched(SyncProdutoSearchDocument::class, function (SyncProdutoSearchDocument $job) use ($produto) {
            $payload = $this->extractPayload($job);

            return $payload['produtoId'] === $produto->getKey()
                && $payload['operation'] === SyncProdutoSearchDocument::OPERATION_UPSERT
                && $payload['queue'] === 'search-sync';
        });
    }

    public function test_dispatch_delete_queues_delete_operation(): void
    {
        Bus::fake();

        $produto = Produto::withoutEvents(fn () => Produto::factory()->create());

        SyncProdutoSearchDocument::dispatchDelete($produto);

        Bus::assertDispatched(SyncProdutoSearchDocument::class, function (SyncProdutoSearchDocument $job) use ($produto) {
            $payload = $this->extractPayload($job);

            return $payload['produtoId'] === $produto->getKey()
                && $payload['operation'] === SyncProdutoSearchDocument::OPERATION_DELETE
                && $payload['queue'] === 'search-sync';
        });
    }

    public function test_handle_indexes_document_for_upsert_operation(): void
    {
        $indexer = Mockery::mock(ProdutoIndexer::class);
        $indexer->shouldReceive('indexById')->once()->with(10);

        $job = new SyncProdutoSearchDocument(10, SyncProdutoSearchDocument::OPERATION_UPSERT);
        $job->handle($indexer);
    }

    public function test_handle_deletes_document_for_delete_operation(): void
    {
        $indexer = Mockery::mock(ProdutoIndexer::class);
        $indexer->shouldReceive('delete')->once()->with(20);

        $job = new SyncProdutoSearchDocument(20, SyncProdutoSearchDocument::OPERATION_DELETE);
        $job->handle($indexer);
    }

    /**
     * @return array{produtoId: int, operation: string, queue: ?string}
     */
    private function extractPayload(SyncProdutoSearchDocument $job): array
    {
        $closure = function () {
            return [
                'produtoId' => $this->produtoId,
                'operation' => $this->operation,
                'queue' => $this->queue,
            ];
        };

        /** @var array{produtoId: int, operation: string, queue: ?string} $payload */
        return $closure->call($job);
    }
}
