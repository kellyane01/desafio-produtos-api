<?php

namespace App\Search;

use App\Models\Produto;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Transport\Exception\NoNodeAvailableException;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Pagination\Paginator as SimplePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProdutoSearchEngine
{
    public function __construct(
        private readonly Client $client,
        private readonly ProdutoIndexConfigurator $indexConfigurator,
    ) {}

    private ?string $lastFailureReason = null;

    public function lastFailureReason(): ?string
    {
        return $this->lastFailureReason;
    }

    public function search(array $filters, int $perPage): ?ProdutoSearchResult
    {
        $this->lastFailureReason = null;

        if (! $this->indexConfigurator->exists()) {
            $this->lastFailureReason = 'index_unavailable';

            return null;
        }

        $page = max(1, (int) ($filters['page'] ?? SimplePaginator::resolveCurrentPage()));
        $searchTerm = trim((string) ($filters['search'] ?? ''));

        if ($searchTerm === '') {
            $this->lastFailureReason = 'empty_search';

            return null;
        }

        $from = ($page - 1) * $perPage;
        $body = [
            'from' => $from,
            'size' => $perPage,
            'query' => $this->buildQuery($searchTerm, $filters),
            'highlight' => $this->highlightConfig(),
            'suggest' => $this->suggestConfig($searchTerm),
        ];

        $sort = $this->buildSortClause($filters);
        if ($sort !== null) {
            $body['sort'] = $sort;
        }

        try {
            $response = $this->client->search([
                'index' => $this->indexConfigurator->indexName(),
                'body' => $body,
            ]);
        } catch (ClientResponseException $exception) {
            $status = $exception->getCode();

            if ($status === 404) {
                Log::warning('Índice de produtos não encontrado no Elasticsearch durante busca.');
                $this->lastFailureReason = 'index_missing';

                return null;
            }

            throw $exception;
        } catch (NoNodeAvailableException) {
            Log::warning('Não foi possível consultar o Elasticsearch: nenhum nó disponível.');

            $this->lastFailureReason = 'no_node_available';

            return null;
        }

        $data = $response->asArray();

        $hits = Arr::get($data, 'hits.hits', []);
        $total = (int) Arr::get($data, 'hits.total.value', 0);
        $maxScore = Arr::get($data, 'hits.max_score');

        if ($total === 0) {
            $paginator = new Paginator(collect(), 0, $perPage, $page, [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]);

            return new ProdutoSearchResult($paginator, suggestions: $this->extractSuggestions($data), highlights: [], usingElasticsearch: true, maxScore: $maxScore !== null ? (float) $maxScore : null);
        }

        $this->lastFailureReason = null;

        $orderedIds = collect($hits)
            ->pluck('_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $highlights = collect($hits)
            ->mapWithKeys(function (array $hit) {
                $id = (int) $hit['_id'];
                $highlight = Arr::get($hit, 'highlight', []);

                if ($highlight === []) {
                    return [];
                }

                $normalized = [];
                foreach ($highlight as $field => $values) {
                    if (! is_array($values) || $values === []) {
                        continue;
                    }

                    $normalized[$field] = $values[0];
                }

                if ($normalized === []) {
                    return [];
                }

                return [$id => $normalized];
            })
            ->all();

        $produtos = Produto::query()
            ->whereIn('id', $orderedIds->all())
            ->get()
            ->keyBy('id');

        $items = $orderedIds
            ->map(fn (int $id) => $produtos->get($id))
            ->filter()
            ->values();

        $items->each(function (Produto $produto) use ($highlights) {
            $id = (int) $produto->getKey();
            if (array_key_exists($id, $highlights)) {
                $produto->search_highlight = $highlights[$id];
            }
        });

        $paginator = new Paginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );

        return new ProdutoSearchResult(
            paginator: $paginator,
            suggestions: $this->extractSuggestions($data),
            highlights: $highlights,
            usingElasticsearch: true,
            maxScore: $maxScore !== null ? (float) $maxScore : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildQuery(string $searchTerm, array $filters): array
    {
        $must = [
            [
                'multi_match' => [
                    'query' => $searchTerm,
                    'fields' => [
                        'nome^4',
                        'descricao^2',
                        'categoria^3',
                    ],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO',
                ],
            ],
        ];

        $filter = $this->buildFilterClauses($filters);

        return [
            'function_score' => [
                'query' => [
                    'bool' => array_filter([
                        'must' => $must,
                        'filter' => $filter,
                    ]),
                ],
                'field_value_factor' => [
                    'field' => 'estoque',
                    'modifier' => 'log1p',
                    'missing' => 1,
                ],
                'boost_mode' => 'sum',
                'score_mode' => 'avg',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildFilterClauses(array $filters): array
    {
        $clauses = [];

        if (! empty($filters['categoria'])) {
            $clauses[] = [
                'term' => [
                    'categoria_terms' => $this->normalizeCategoria($filters['categoria']),
                ],
            ];
        }

        if (! empty($filters['categorias'])) {
            $categories = $this->normalizeCategories($filters['categorias']);
            if (! empty($categories)) {
                $clauses[] = [
                    'terms' => [
                        'categoria_terms' => $categories,
                    ],
                ];
            }
        }

        if (isset($filters['min_preco']) && is_numeric($filters['min_preco'])) {
            $clauses[] = [
                'range' => [
                    'preco' => ['gte' => (float) $filters['min_preco']],
                ],
            ];
        }

        if (isset($filters['max_preco']) && is_numeric($filters['max_preco'])) {
            $clauses[] = [
                'range' => [
                    'preco' => ['lte' => (float) $filters['max_preco']],
                ],
            ];
        }

        if (array_key_exists('disponivel', $filters)) {
            $value = filter_var($filters['disponivel'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value !== null) {
                $clauses[] = [
                    'term' => ['disponivel' => $value],
                ];
            }
        }

        return $clauses;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildSortClause(array $filters): ?array
    {
        $sortableFields = [
            'nome' => 'nome.raw',
            'preco' => 'preco',
            'categoria' => 'categoria',
            'estoque' => 'estoque',
            'created_at' => 'created_at',
        ];

        $sort = $filters['sort'] ?? null;
        if ($sort === null || ! array_key_exists($sort, $sortableFields)) {
            return null;
        }

        $direction = strtolower((string) ($filters['order'] ?? 'asc'));
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        return [
            ['_score' => 'desc'],
            [$sortableFields[$sort] => ['order' => $direction]],
        ];
    }

    private function highlightConfig(): array
    {
        return [
            'pre_tags' => ['<em>'],
            'post_tags' => ['</em>'],
            'fields' => [
                'nome' => new \stdClass,
                'descricao' => new \stdClass,
            ],
        ];
    }

    private function suggestConfig(string $searchTerm): array
    {
        $size = max(1, (int) config('elasticsearch.suggestion_size', 5));

        return [
            'produto_completion' => [
                'prefix' => $searchTerm,
                'completion' => [
                    'field' => 'nome_suggest',
                    'skip_duplicates' => true,
                    'size' => $size,
                ],
            ],
            'produto_terms' => [
                'text' => $searchTerm,
                'term' => [
                    'field' => 'nome',
                    'suggest_mode' => 'popular',
                    'size' => $size,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<int, string>
     */
    private function extractSuggestions(array $response): array
    {
        $completion = collect(Arr::get($response, 'suggest.produto_completion', []))
            ->flatMap(fn (array $suggest) => Arr::get($suggest, 'options', []))
            ->pluck('text');

        $terms = collect(Arr::get($response, 'suggest.produto_terms', []))
            ->flatMap(fn (array $suggest) => Arr::get($suggest, 'options', []))
            ->pluck('text');

        return $completion
            ->merge($terms)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>|string  $categorias
     * @return array<int, string>
     */
    private function normalizeCategories(array|string $categorias): array
    {
        $raw = is_array($categorias) ? $categorias : explode(',', (string) $categorias);

        return collect($raw)
            ->map(fn ($value) => $this->normalizeCategoria($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeCategoria(?string $categoria): ?string
    {
        if ($categoria === null) {
            return null;
        }

        $normalized = Str::of($categoria)
            ->lower()
            ->squish()
            ->toString();

        return $normalized === '' ? null : $normalized;
    }
}
