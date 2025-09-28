<?php

namespace Tests\Unit\Services;

use App\Repositories\ProdutoRepositoryInterface;
use App\Search\ProdutoSearchEngine;
use App\Search\ProdutoSearchResult;
use App\Search\SearchHealthReporter;
use App\Services\ProdutoService;
use Illuminate\Cache\TaggableStore;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ProdutoServiceTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    public function test_list_returns_search_result_and_records_success_when_elasticsearch_succeeds(): void
    {
        $repository = Mockery::mock(ProdutoRepositoryInterface::class);
        $searchEngine = Mockery::mock(ProdutoSearchEngine::class);
        $healthReporter = Mockery::mock(SearchHealthReporter::class);

        $paginator = new LengthAwarePaginator(collect(), 0, 15);
        $expectedResult = new ProdutoSearchResult($paginator, usingElasticsearch: true);

        $searchEngine->shouldReceive('search')
            ->once()
            ->withArgs(function (array $filters, int $perPage) {
                TestCase::assertSame('monitor', $filters['search']);
                TestCase::assertArrayNotHasKey('per_page', $filters);
                TestCase::assertSame(15, $perPage);

                return true;
            })
            ->andReturn($expectedResult);

        $healthReporter->shouldReceive('recordSuccess')->once();

        $service = new ProdutoService($repository, $searchEngine, $healthReporter);

        $result = $service->list(['search' => ' monitor '], 15);

        $this->assertSame($expectedResult, $result);
    }

    public function test_list_records_failure_and_flushes_cache_when_search_engine_fails_with_invalidation_reason(): void
    {
        $repository = Mockery::mock(ProdutoRepositoryInterface::class);
        $searchEngine = Mockery::mock(ProdutoSearchEngine::class);
        $healthReporter = Mockery::mock(SearchHealthReporter::class);

        $paginator = new LengthAwarePaginator(collect(['fallback']), 1, 15);

        $searchEngine->shouldReceive('search')
            ->once()
            ->andReturnNull();

        $searchEngine->shouldReceive('lastFailureReason')
            ->once()
            ->andReturn('index_missing');

        $healthReporter->shouldReceive('recordFailure')
            ->once()
            ->with('index_missing', Mockery::on(function (array $context) {
                TestCase::assertSame([
                    'search' => 'monitor',
                ], $context['filters']);

                return true;
            }));

        $taggableStore = Mockery::mock(TaggableStore::class);
        $cacheTags = Mockery::mock();
        Cache::shouldReceive('getStore')->once()->andReturn($taggableStore);
        Cache::shouldReceive('tags')->once()->with(['produtos'])->andReturn($cacheTags);
        $cacheTags->shouldReceive('flush')->once();

        $repository->shouldReceive('paginate')
            ->once()
            ->with(['search' => 'monitor'], 15)
            ->andReturn($paginator);

        $service = new ProdutoService($repository, $searchEngine, $healthReporter);

        $result = $service->list(['search' => 'monitor'], 15);

        $this->assertFalse($result->usingElasticsearch());
        $this->assertSame($paginator, $result->paginator());
    }

    public function test_list_skips_search_when_term_is_blank(): void
    {
        $repository = Mockery::mock(ProdutoRepositoryInterface::class);
        $searchEngine = Mockery::mock(ProdutoSearchEngine::class);
        $healthReporter = Mockery::mock(SearchHealthReporter::class);

        $paginator = new LengthAwarePaginator(collect(), 0, 10);

        $searchEngine->shouldReceive('search')->never();
        $searchEngine->shouldReceive('lastFailureReason')->never();
        $healthReporter->shouldReceive('recordFailure')->never();
        $healthReporter->shouldReceive('recordSuccess')->never();

        $repository->shouldReceive('paginate')
            ->once()
            ->with([], 10)
            ->andReturn($paginator);

        $service = new ProdutoService($repository, $searchEngine, $healthReporter);

        $result = $service->list(['search' => '   '], 10);

        $this->assertSame($paginator, $result->paginator());
        $this->assertFalse($result->usingElasticsearch());
    }
}
