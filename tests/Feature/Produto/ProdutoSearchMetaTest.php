<?php

namespace Tests\Feature\Produto;

use App\Models\Produto;
use App\Models\User;
use App\Search\ProdutoSearchEngine;
use App\Search\ProdutoSearchResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class ProdutoSearchMetaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_search_response_includes_meta_and_highlights_from_elasticsearch(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $produto = Produto::factory()->create([
            'nome' => 'Notebook Gamer Z15',
            'descricao' => 'GPU dedicada com 8 GB',
        ]);

        $produto->search_highlight = [
            'nome' => 'Notebook <em>Gamer</em> Z15',
            'descricao' => 'GPU dedicada com <em>8</em> GB',
        ];

        $paginator = new LengthAwarePaginator(
            collect([$produto]),
            1,
            15,
            1,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );

        $searchResult = new ProdutoSearchResult(
            paginator: $paginator,
            suggestions: ['notebook gamer', 'notebook games'],
            highlights: [],
            usingElasticsearch: true,
            maxScore: 9.1,
        );

        $searchEngine = Mockery::mock(ProdutoSearchEngine::class);
        $searchEngine->shouldReceive('search')
            ->once()
            ->withArgs(function (array $filters, int $perPage) {
                return ($filters['search'] ?? null) === 'notebook' && $perPage === 15;
            })
            ->andReturn($searchResult);

        $this->app->instance(ProdutoSearchEngine::class, $searchEngine);

        $response = $this->getJson('/api/v1/produtos?search=notebook');

        $response
            ->assertOk()
            ->assertJsonPath('meta.search.engine', 'elasticsearch')
            ->assertJsonPath('meta.search.suggestions.0', 'notebook gamer')
            ->assertJsonPath('meta.search.max_score', 9.1)
            ->assertJsonPath('data.0.nome', 'Notebook Gamer Z15')
            ->assertJsonPath('data.0.search_highlight.nome', 'Notebook <em>Gamer</em> Z15');
    }
}
