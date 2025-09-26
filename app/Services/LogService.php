<?php

namespace App\Services;

use App\Repositories\LogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LogService
{
    public function __construct(private readonly LogRepositoryInterface $repository)
    {
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }
}
