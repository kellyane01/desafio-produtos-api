<?php

namespace App\Repositories;

use App\Models\Produto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProdutoRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function create(array $attributes): Produto;

    public function update(Produto $produto, array $attributes): Produto;

    public function delete(Produto $produto): void;
}
