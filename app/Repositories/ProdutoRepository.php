<?php

namespace App\Repositories;

use App\Models\Produto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;

class ProdutoRepository implements ProdutoRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Produto::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($query) use ($search) {
                $query->where('nome', 'like', "%{$search}%")
                    ->orWhere('descricao', 'like', "%{$search}%")
                    ->orWhere('categoria', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['categoria'])) {
            $query->where('categoria', $filters['categoria']);
        }

        $normalizedCategories = null;
        if (!empty($filters['categorias'])) {
            $rawCategories = is_array($filters['categorias'])
                ? $filters['categorias']
                : explode(',', $filters['categorias']);

            $categoryList = array_values(array_filter(array_map('trim', $rawCategories)));

            if (!empty($categoryList)) {
                sort($categoryList);
                $normalizedCategories = $categoryList;
                $query->whereIn('categoria', $categoryList);
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
        if (!in_array($sortColumn, $sortableColumns, true)) {
            $sortColumn = 'nome';
        }

        $sortDirection = strtolower($filters['order'] ?? 'asc');
        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'asc';
        }

        $query->orderBy($sortColumn, $sortDirection);

        $page = isset($filters['page']) && (int) $filters['page'] > 0
            ? (int) $filters['page']
            : Paginator::resolveCurrentPage();

        $cacheKey = sprintf(
            'produtos:%s',
            md5(json_encode([
                'search' => $filters['search'] ?? null,
                'categoria' => $filters['categoria'] ?? null,
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

        Cache::tags(['produtos'])->flush();

        return $produto;
    }

    public function update(Produto $produto, array $attributes): Produto
    {
        $produto->update($attributes);

        Cache::tags(['produtos'])->flush();

        return $produto;
    }

    public function delete(Produto $produto): void
    {
        $produto->delete();

        Cache::tags(['produtos'])->flush();
    }
}
