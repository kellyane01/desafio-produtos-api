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

    /**
     * @group Produtos
     * Lista produtos com suporte a filtros, ordenação e paginação.
     *
     * @queryParam search string Busca textual por nome, descrição ou categoria. Example: smartphone
     * @queryParam categoria string Filtra por uma categoria específica. Example: Eletrônicos
     * @queryParam categorias[] string Filtra por múltiplas categorias (aceita array ou valores separados por vírgula). Example: ["Eletrônicos","Informática"]
     * @queryParam min_preco number Limita a busca pelo preço mínimo. Example: 100.9
     * @queryParam max_preco number Limita a busca pelo preço máximo. Example: 999.9
     * @queryParam disponivel boolean Retorna apenas produtos com estoque (>0) quando true ou esgotados quando false. Example: true
     * @queryParam sort string Campo utilizado na ordenação (nome, preco, categoria, estoque, created_at). Example: preco
     * @queryParam order string Direção da ordenação (asc ou desc). Example: desc
     * @queryParam page integer Número da página a ser retornada. Example: 2
     * @queryParam per_page integer Quantidade de registros por página (1-100). Example: 25
     *
     * @responseField data[].id integer Identificador do produto.
     * @responseField data[].nome string Nome do produto.
     * @responseField data[].descricao string Descrição detalhada.
     * @responseField data[].preco number Preço unitário do produto.
     * @responseField data[].categoria string Categoria cadastrada.
     * @responseField data[].estoque integer Quantidade disponível em estoque.
     * @responseField data[].created_at string Data de criação em formato ISO 8601.
     * @responseField data[].updated_at string Última atualização em formato ISO 8601.
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 15,
     *       "nome": "Smartphone X",
     *       "descricao": "Tela AMOLED de 6.1" e câmera dupla de 48MP.",
     *       "preco": 3499.9,
     *       "categoria": "Eletrônicos",
     *       "estoque": 12,
     *       "created_at": "2024-05-10T12:30:45Z",
     *       "updated_at": "2024-07-02T16:18:03Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/v1/produtos?page=1",
     *     "last": "http://localhost/api/v1/produtos?page=5",
     *     "prev": null,
     *     "next": "http://localhost/api/v1/produtos?page=2"
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 5,
     *     "path": "http://localhost/api/v1/produtos",
     *     "per_page": 15,
     *     "to": 15,
     *     "total": 75
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'categoria',
            'categorias',
            'min_preco',
            'max_preco',
            'disponivel',
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

        $result = $this->service->list($filters, $perPage);

        $paginator = $result->paginator();
        $paginator->appends($request->query());

        $collection = ProdutoResource::collection($paginator);

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

    /**
     * @group Produtos
     * Cadastra um novo produto.
     *
     * @bodyParam nome string required Nome do produto. Example: Caixa de Som Bluetooth
     * @bodyParam descricao string required Descrição do produto. Example: Caixa portátil com bateria de 12h e proteção IP67
     * @bodyParam preco number required Preço unitário. Example: 299.9
     * @bodyParam categoria string required Categoria do produto. Example: Áudio
     * @bodyParam estoque integer required Quantidade inicial em estoque. Example: 20
     *
     * @response 201 {
     *   "id": 21,
     *   "nome": "Caixa de Som Bluetooth",
     *   "descricao": "Caixa portátil com bateria de 12h e proteção IP67",
     *   "preco": 299.9,
     *   "categoria": "Áudio",
     *   "estoque": 20,
     *   "created_at": "2024-07-10T10:12:05Z",
     *   "updated_at": "2024-07-10T10:12:05Z"
     * }
     */
    public function store(StoreProdutoRequest $request): JsonResponse
    {
        $produto = $this->service->create($request->validated());

        return ProdutoResource::make($produto)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * @group Produtos
     * Exibe os detalhes de um produto específico.
     *
     * @urlParam produto integer required Identificador do produto. Example: 21
     *
     * @response 200 {
     *   "id": 21,
     *   "nome": "Caixa de Som Bluetooth",
     *   "descricao": "Caixa portátil com bateria de 12h e proteção IP67",
     *   "preco": 299.9,
     *   "categoria": "Áudio",
     *   "estoque": 20,
     *   "created_at": "2024-07-10T10:12:05Z",
     *   "updated_at": "2024-07-10T10:12:05Z"
     * }
     */
    public function show(Produto $produto): JsonResponse
    {
        return ProdutoResource::make($produto)->response();
    }

    /**
     * @group Produtos
     * Atualiza os dados de um produto.
     *
     * @urlParam produto integer required Identificador do produto. Example: 21
     * @bodyParam nome string Nome do produto. Informe apenas quando desejar atualizar. Example: Caixa de Som Bluetooth Pro
     * @bodyParam descricao string Descrição detalhada. Example: Versão com cancelamento de ruído ativo
     * @bodyParam preco number Preço unitário. Example: 349.9
     * @bodyParam categoria string Categoria do produto. Example: Áudio
     * @bodyParam estoque integer Quantidade disponível. Example: 18
     *
     * @response 200 {
     *   "id": 21,
     *   "nome": "Caixa de Som Bluetooth Pro",
     *   "descricao": "Versão com cancelamento de ruído ativo",
     *   "preco": 349.9,
     *   "categoria": "Áudio",
     *   "estoque": 18,
     *   "created_at": "2024-07-10T10:12:05Z",
     *   "updated_at": "2024-07-12T08:43:27Z"
     * }
     */
    public function update(UpdateProdutoRequest $request, Produto $produto): JsonResponse
    {
        $produto = $this->service->update($produto, $request->validated());

        return ProdutoResource::make($produto)->response();
    }

    /**
     * @group Produtos
     * Remove um produto e libera o registro associado.
     *
     * @urlParam produto integer required Identificador do produto. Example: 21
     *
     * @response 204 {}
     */
    public function destroy(Produto $produto): Response
    {
        $this->service->delete($produto);

        return response()->noContent();
    }
}
