<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Produto\StoreProdutoRequest;
use App\Http\Requests\Produto\UpdateProdutoRequest;
use App\Http\Resources\ProdutoResource;
use App\Models\Produto;
use App\Services\ProdutoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProdutoController extends Controller
{
    public function __construct(private readonly ProdutoService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'categoria',
            'min_preco',
            'max_preco',
            'sort',
            'order',
            'page',
        ]);

        $filters['page'] = isset($filters['page']) && (int) $filters['page'] > 0
            ? (int) $filters['page']
            : (int) $request->query('page', 1);

        $perPage = (int) $request->query('per_page', 15);
        if ($perPage <= 0) {
            $perPage = 15;
        }

        $produtos = $this->service->list($filters, $perPage);

        return ProdutoResource::collection($produtos)->response();
    }

    public function store(StoreProdutoRequest $request): JsonResponse
    {
        $produto = $this->service->create($request->validated());

        return ProdutoResource::make($produto)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Produto $produto): JsonResponse
    {
        return ProdutoResource::make($produto)->response();
    }

    public function update(UpdateProdutoRequest $request, Produto $produto): JsonResponse
    {
        $produto = $this->service->update($produto, $request->validated());

        return ProdutoResource::make($produto)->response();
    }

    public function destroy(Produto $produto): Response
    {
        $this->service->delete($produto);

        return response()->noContent();
    }
}
