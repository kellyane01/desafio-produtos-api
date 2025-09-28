<?php

namespace Tests\Unit\Search;

use App\Models\Produto;
use App\Search\ProdutoIndexConfigurator;
use App\Search\ProdutoSearchEngine;
use Elastic\Elasticsearch\ClientInterface;
use Elastic\Transport\Exception\NoNodeAvailableException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProdutoSearchEngineTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    public function test_search_returns_paginated_results_with_highlights_and_suggestions(): void
    {
        $produto = Produto::factory()->create([
            'nome' => 'Notebook Gamer X',
            'descricao' => '16GB RAM',
        ]);

        $client = Mockery::mock(ClientInterface::class);
        $configurator = Mockery::mock(ProdutoIndexConfigurator::class);

        $configurator->shouldReceive('exists')->once()->andReturn(true);
        $configurator->shouldReceive('indexName')->andReturn('produtos');

        $expectedBody = [
            'hits' => [
                'total' => ['value' => 1],
                'max_score' => 9.5,
                'hits' => [[
                    '_id' => (string) $produto->id,
                    'highlight' => [
                        'nome' => ['Notebook <em>Gamer</em> X'],
                        'descricao' => ['16GB <em>RAM</em>'],
                    ],
                ]],
            ],
            'suggest' => [
                'produto_completion' => [[
                    'options' => [['text' => 'notebook gamer x']],
                ]],
                'produto_terms' => [[
                    'options' => [['text' => 'notebook gamer']],
                ]],
            ],
        ];

        $client->shouldReceive('search')
            ->once()
            ->withArgs(function (array $params) {
                TestCase::assertSame('produtos', $params['index']);

                $body = $params['body'];
                TestCase::assertSame(0, $body['from']);
                TestCase::assertSame(15, $body['size']);
                TestCase::assertSame('notebook', $body['query']['function_score']['query']['bool']['must'][0]['multi_match']['query']);
                TestCase::assertSame('desc', $body['sort'][1]['preco']['order']);
                TestCase::assertSame(['<em>'], $body['highlight']['pre_tags']);
                TestCase::assertSame('notebook', $body['suggest']['produto_completion']['prefix']);

                return true;
            })
            ->andReturn(new class($expectedBody)
            {
                public function __construct(private array $payload) {}

                public function asArray(): array
                {
                    return $this->payload;
                }
            });

        $engine = new ProdutoSearchEngine($client, $configurator);

        $result = $engine->search([
            'search' => 'notebook',
            'categoria' => 'Informática',
            'categorias' => ['Informática', 'Games'],
            'min_preco' => 100,
            'max_preco' => 5000,
            'disponivel' => true,
            'sort' => 'preco',
            'order' => 'desc',
        ], 15);

        $this->assertNotNull($result);
        $this->assertTrue($result->usingElasticsearch());
        $this->assertSame(1, $result->paginator()->total());
        $this->assertSame(9.5, $result->maxScore());

        $item = $result->paginator()->items()[0];
        $this->assertSame('Notebook <em>Gamer</em> X', $item->search_highlight['nome']);
        $this->assertSame('16GB <em>RAM</em>', $item->search_highlight['descricao']);

        $this->assertSame(['notebook gamer x', 'notebook gamer'], $result->suggestions());
        $this->assertNull($engine->lastFailureReason());
    }

    public function test_search_returns_null_when_index_is_unavailable(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $configurator = Mockery::mock(ProdutoIndexConfigurator::class);

        $configurator->shouldReceive('exists')->once()->andReturn(false);

        $client->shouldReceive('search')->never();

        $engine = new ProdutoSearchEngine($client, $configurator);

        $result = $engine->search(['search' => 'cadeira'], 10);

        $this->assertNull($result);
        $this->assertSame('index_unavailable', $engine->lastFailureReason());
    }

    public function test_search_returns_null_when_term_is_empty(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $configurator = Mockery::mock(ProdutoIndexConfigurator::class);

        $configurator->shouldReceive('exists')->once()->andReturn(true);

        $client->shouldReceive('search')->never();

        $engine = new ProdutoSearchEngine($client, $configurator);

        $result = $engine->search(['search' => '   '], 10);

        $this->assertNull($result);
        $this->assertSame('empty_search', $engine->lastFailureReason());
    }

    public function test_search_returns_null_and_sets_reason_when_no_node_available(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $configurator = Mockery::mock(ProdutoIndexConfigurator::class);

        $configurator->shouldReceive('exists')->once()->andReturn(true);
        $configurator->shouldReceive('indexName')->andReturn('produtos');

        $client->shouldReceive('search')->andThrow(new NoNodeAvailableException('no node available'));

        $engine = new ProdutoSearchEngine($client, $configurator);

        $result = $engine->search(['search' => 'cadeira'], 10);

        $this->assertNull($result);
        $this->assertSame('no_node_available', $engine->lastFailureReason());
    }
}
