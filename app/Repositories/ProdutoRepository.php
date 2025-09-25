<?php

namespace App\Repositories;

use App\Models\Produto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProdutoRepository implements ProdutoRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Produto::query();

        if (isset($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($query) use ($search) {
                $query->where('nome', 'like', "%{$search}%")
                    ->orWhere('descricao', 'like', "%{$search}%")
                    ->orWhere('categoria', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('nome')->paginate($perPage);
    }

    public function create(array $attributes): Produto
    {
        return Produto::create($attributes);
    }

    public function update(Produto $produto, array $attributes): Produto
    {
        $produto->update($attributes);

        return $produto;
    }

    public function delete(Produto $produto): void
    {
        $produto->delete();
    }
}
