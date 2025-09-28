<?php

namespace App\Services;

use App\Models\Produto;
use App\Repositories\ProdutoRepositoryInterface;
use App\Search\ProdutoSearchEngine;
use App\Search\ProdutoSearchResult;
use App\Search\SearchHealthReporter;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;

class ProdutoService
{
    private const CACHE_INVALIDATION_REASONS = [
        'index_unavailable',
        'index_missing',
        'no_node_available',
    ];

    public function __construct(
        private readonly ProdutoRepositoryInterface $repository,
        private readonly ProdutoSearchEngine $searchEngine,
        private readonly SearchHealthReporter $searchHealthReporter,
    ) {}

    public function list(array $filters = [], int $perPage = 15): ProdutoSearchResult
    {
        if (isset($filters['search']) && is_string($filters['search'])) {
            $filters['search'] = trim($filters['search']);

            if ($filters['search'] === '') {
                unset($filters['search']);
            }
        }

        if (! empty($filters['search'])) {
            $result = $this->searchEngine->search($filters, $perPage);

            if ($result !== null) {
                $this->searchHealthReporter->recordSuccess();

                return $result;
            }

            $reason = $this->searchEngine->lastFailureReason() ?? 'unknown';

            if ($reason !== 'empty_search') {
                $this->searchHealthReporter->recordFailure($reason, [
                    'filters' => array_intersect_key($filters, array_flip([
                        'search',
                        'categoria',
                        'categorias',
                        'min_preco',
                        'max_preco',
                        'disponivel',
                        'sort',
                        'order',
                    ])),
                ]);

                if ($this->shouldFlushCachedSearchResults($reason)) {
                    $this->flushCachedSearchResults();
                }
            }
        }

        return ProdutoSearchResult::wrap($this->repository->paginate($filters, $perPage));
    }

    public function create(array $data): Produto
    {
        return $this->repository->create($data);
    }

    public function update(Produto $produto, array $data): Produto
    {
        return $this->repository->update($produto, $data);
    }

    public function delete(Produto $produto): void
    {
        $this->repository->delete($produto);
    }

    private function flushCachedSearchResults(): void
    {
        $store = Cache::getStore();

        if ($store instanceof TaggableStore) {
            Cache::tags(['produtos'])->flush();
        }
    }

    private function shouldFlushCachedSearchResults(string $reason): bool
    {
        return in_array($reason, self::CACHE_INVALIDATION_REASONS, true);
    }
}
