<?php

namespace App\Services;

use App\Models\Produto;
use App\Repositories\ProdutoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProdutoService
{
    public function __construct(private readonly ProdutoRepositoryInterface $repository)
    {
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
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
}
