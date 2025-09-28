<?php

namespace Tests\Feature\Console;

use App\Models\Produto;
use App\Search\ProdutoIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ReindexProdutosCommandTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    public function test_command_reindexes_all_products_using_existing_index(): void
    {
        $produtos = Produto::factory()->count(3)->create();

        $indexer = Mockery::mock(ProdutoIndexer::class);
        $indexer->shouldReceive('ensureIndex')->once();
        $indexer->shouldReceive('bulk')
            ->once()
            ->withArgs(function ($collection) use ($produtos) {
                return $collection->count() === $produtos->count();
            });

        app()->instance(ProdutoIndexer::class, $indexer);

        $this->artisan('produto:search:reindex')
            ->expectsOutputToContain('Indexação concluída com sucesso.')
            ->assertExitCode(0);
    }

    public function test_command_recreates_index_when_fresh_option_is_used(): void
    {
        $produtos = Produto::factory()->count(2)->create();

        $indexer = Mockery::mock(ProdutoIndexer::class);
        $indexer->shouldReceive('recreateIndex')->once();
        $indexer->shouldReceive('bulk')->once();

        app()->instance(ProdutoIndexer::class, $indexer);

        $this->artisan('produto:search:reindex --fresh')
            ->expectsOutputToContain('Recriando índice do Elasticsearch...')
            ->assertExitCode(0);
    }

    public function test_command_honors_chunk_option(): void
    {
        Produto::factory()->count(5)->create();

        $indexer = Mockery::mock(ProdutoIndexer::class);
        $indexer->shouldReceive('ensureIndex')->once();
        $indexer->shouldReceive('bulk')->times(3);

        app()->instance(ProdutoIndexer::class, $indexer);

        $this->artisan('produto:search:reindex --chunk=2')->assertExitCode(0);
    }
}
