<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Produto\StoreProdutoRequest;
use App\Http\Requests\Produto\UpdateProdutoRequest;
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
        $filters = $request->only(['search']);
        $perPage = (int) $request->query('per_page', 15);

        $produtos = $this->service->list($filters, $perPage > 0 ? $perPage : 15);

        return response()->json($produtos);
    }

    public function store(StoreProdutoRequest $request): JsonResponse
    {
        $produto = $this->service->create($request->validated());

        return response()->json($produto, Response::HTTP_CREATED);
    }

    public function show(Produto $produto): JsonResponse
    {
        return response()->json($produto);
    }

    public function update(UpdateProdutoRequest $request, Produto $produto): JsonResponse
    {
        $produto = $this->service->update($produto, $request->validated());

        return response()->json($produto);
    }

    public function destroy(Produto $produto): Response
    {
        $this->service->delete($produto);

        return response()->noContent();
    }
}
