<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Produto\ListProdutoRequest;
use App\Http\Requests\Produto\StoreProdutoRequest;
use App\Http\Requests\Produto\UpdateProdutoRequest;
use App\Http\Resources\ProdutoListResource;
use App\Http\Resources\ProdutoResource;
use App\Models\Produto;
use App\Services\ProdutoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProdutoController extends Controller
{
    public function __construct(private readonly ProdutoService $service) {}

    public function index(ListProdutoRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = collect($validated)
            ->except(['per_page'])
            ->all();

        $filters['page'] = $validated['page'] ?? (int) $request->query('page', 1);

        $perPage = (int) ($validated['per_page'] ?? $request->query('per_page', 15));
        if ($perPage <= 0) {
            $perPage = 15;
        }

        $result = $this->service->list($filters, $perPage);

        $paginator = $result->paginator();
        $paginator->appends($request->query());

        $collection = ProdutoListResource::collection($paginator);

        if ($result->usingElasticsearch()) {
            $searchMeta = [
                'engine' => 'elasticsearch',
                'suggestions' => $result->suggestions(),
            ];

            if (($maxScore = $result->maxScore()) !== null) {
                $searchMeta['max_score'] = $maxScore;
            }

            return $collection
                ->additional(['meta' => ['search' => $searchMeta]])
                ->response();
        }

        return $collection->response();
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
