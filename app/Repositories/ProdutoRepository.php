<?php

namespace App\Repositories;

use App\Models\Produto;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProdutoRepository implements ProdutoRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Produto::query();

        $searchTerm = null;
        if (! empty($filters['search'])) {
            $candidate = trim((string) $filters['search']);
            if ($candidate !== '') {
                $searchTerm = mb_strtolower($candidate);
                $like = '%'.$searchTerm.'%';

                $query->where(function ($query) use ($like) {
                    $query->whereRaw('LOWER(nome) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(descricao) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(categoria) LIKE ?', [$like]);
                });
            }
        }

        $categoriaFilter = null;
        if (! empty($filters['categoria'])) {
            $candidate = trim((string) $filters['categoria']);
            if ($candidate !== '') {
                $categoriaFilter = mb_strtolower($candidate);
                $query->whereRaw('LOWER(categoria) = ?', [$categoriaFilter]);
            }
        }

        $normalizedCategories = null;
        if (! empty($filters['categorias'])) {
            $rawCategories = is_array($filters['categorias'])
                ? $filters['categorias']
                : explode(',', (string) $filters['categorias']);

            $categoryList = array_values(array_unique(array_filter(array_map(
                static fn ($value) => mb_strtolower(trim((string) $value)),
                $rawCategories
            ))));

            if (! empty($categoryList)) {
                sort($categoryList);
                $normalizedCategories = $categoryList;
                $query->whereIn(DB::raw('LOWER(categoria)'), $categoryList);
            }
        }

        if (isset($filters['min_preco']) && is_numeric($filters['min_preco'])) {
            $query->where('preco', '>=', (float) $filters['min_preco']);
        }

        if (isset($filters['max_preco']) && is_numeric($filters['max_preco'])) {
            $query->where('preco', '<=', (float) $filters['max_preco']);
        }

        $availableFilter = null;
        if (array_key_exists('disponivel', $filters)) {
            $availableFilter = filter_var($filters['disponivel'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($availableFilter !== null) {
                if ($availableFilter) {
                    $query->where('estoque', '>', 0);
                } else {
                    $query->where('estoque', '<=', 0);
                }
            }
        }

        $sortableColumns = ['nome', 'preco', 'categoria', 'estoque', 'created_at'];
        $sortColumn = $filters['sort'] ?? 'nome';
        if (! in_array($sortColumn, $sortableColumns, true)) {
            $sortColumn = 'nome';
        }

        $sortDirection = strtolower((string) ($filters['order'] ?? 'asc'));
        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'asc';
        }

        $query->orderBy($sortColumn, $sortDirection);

        $page = isset($filters['page']) && (int) $filters['page'] > 0
            ? (int) $filters['page']
            : Paginator::resolveCurrentPage();

        $cacheStore = Cache::getStore();
        if (! $cacheStore instanceof TaggableStore) {
            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        $cacheKey = sprintf(
            'produtos:%s',
            md5(json_encode([
                'search' => $searchTerm,
                'categoria' => $categoriaFilter,
                'categorias' => $normalizedCategories,
                'min_preco' => isset($filters['min_preco']) ? (float) $filters['min_preco'] : null,
                'max_preco' => isset($filters['max_preco']) ? (float) $filters['max_preco'] : null,
                'disponivel' => $availableFilter,
                'sort' => $sortColumn,
                'order' => $sortDirection,
                'per_page' => $perPage,
                'page' => $page,
            ]))
        );

        return Cache::tags(['produtos'])->remember(
            $cacheKey,
            now()->addMinutes(5),
            fn () => $query->paginate($perPage, ['*'], 'page', $page)
        );
    }

    public function create(array $attributes): Produto
    {
        $produto = Produto::create($attributes);

        $this->flushCache();

        return $produto;
    }

    public function update(Produto $produto, array $attributes): Produto
    {
        $produto->update($attributes);

        $this->flushCache();

        return $produto;
    }

    public function delete(Produto $produto): void
    {
        $produto->delete();

        $this->flushCache();
    }

    private function flushCache(): void
    {
        $store = Cache::getStore();

        if ($store instanceof TaggableStore) {
            Cache::tags(['produtos'])->flush();
        }
    }
}
