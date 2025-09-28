<?php

namespace Tests\Unit\Search;

use App\Models\Produto;
use App\Search\ProdutoIndexConfigurator;
use App\Search\ProdutoIndexer;
use App\Search\ProdutoSearchDocument;
use Elastic\Elasticsearch\ClientInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProdutoIndexerTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    public function test_index_persists_document_with_expected_payload(): void
    {
        $produto = Produto::factory()->create([
            'nome' => 'Headset Surround',
            'categoria' => 'Audio',
            'estoque' => 5,
        ]);

        $client = Mockery::mock(ClientInterface::class);
        $configurator = Mockery::mock(ProdutoIndexConfigurator::class);

        $configurator->shouldReceive('ensureExists')->once();
        $configurator->shouldReceive('indexName')->andReturn('produtos');

        $expectedDocument = ProdutoSearchDocument::fromModel($produto);

        $client->shouldReceive('index')
            ->once()
            ->withArgs(function (array $params) use ($produto, $expectedDocument) {
                TestCase::assertSame('produtos', $params['index']);
                TestCase::assertSame((string) $produto->getKey(), $params['id']);
                TestCase::assertSame($expectedDocument, $params['body']);
                TestCase::assertFalse($params['refresh']);

                return true;
            });

        $indexer = new ProdutoIndexer($client, $configurator);
        $indexer->index($produto);
    }

    public function test_index_by_id_deletes_when_produto_missing(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $configurator = Mockery::mock(ProdutoIndexConfigurator::class);

        $configurator->shouldReceive('exists')->once()->andReturn(true);
        $configurator->shouldReceive('indexName')->andReturn('produtos');

        $client->shouldReceive('delete')
            ->once()
            ->withArgs(function (array $params) {
                TestCase::assertSame('produtos', $params['index']);
                TestCase::assertSame('999', $params['id']);

                return true;
            });

        $indexer = new ProdutoIndexer($client, $configurator);
        $indexer->indexById(999);
    }

    public function test_bulk_indexes_every_product(): void
    {
        $produtos = Produto::factory()->count(2)->create();

        $client = Mockery::mock(ClientInterface::class);
        $configurator = Mockery::mock(ProdutoIndexConfigurator::class);

        $configurator->shouldReceive('ensureExists')->once();
        $configurator->shouldReceive('indexName')->andReturn('produtos');

        $client->shouldReceive('bulk')
            ->once()
            ->withArgs(function (array $params) use ($produtos) {
                TestCase::assertFalse($params['refresh']);
                $body = $params['body'];
                TestCase::assertCount($produtos->count() * 2, $body);

                foreach ($produtos as $produto) {
                    $action = array_shift($body);
                    $document = array_shift($body);

                    TestCase::assertSame(['index' => ['_index' => 'produtos', '_id' => (string) $produto->getKey()]], $action);
                    TestCase::assertSame(ProdutoSearchDocument::fromModel($produto), $document);
                }

                return true;
            });

        $indexer = new ProdutoIndexer($client, $configurator);
        $indexer->bulk($produtos);
    }

    public function test_bulk_avoids_calls_when_collection_is_empty(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $configurator = Mockery::mock(ProdutoIndexConfigurator::class);

        $configurator->shouldReceive('ensureExists')->never();
        $client->shouldReceive('bulk')->never();

        $indexer = new ProdutoIndexer($client, $configurator);
        $indexer->bulk(new EloquentCollection());
    }

    public function test_delete_does_nothing_when_index_missing(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $configurator = Mockery::mock(ProdutoIndexConfigurator::class);

        $configurator->shouldReceive('exists')->once()->andReturn(false);
        $client->shouldReceive('delete')->never();

        $indexer = new ProdutoIndexer($client, $configurator);
        $indexer->delete(1000);
    }
}
