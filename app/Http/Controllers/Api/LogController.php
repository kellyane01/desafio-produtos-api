<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Log\ListLogRequest;
use App\Http\Resources\LogResource;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;

class LogController extends Controller
{
    public function __construct(private readonly LogService $service)
    {
    }

    /**
     * @group Logs
     * Lista os logs de auditoria aplicando filtros e paginação.
     *
     * @queryParam model string Filtra pelo nome completo do modelo monitorado (ex.: App\Models\Produto). Example: App\Models\Produto
     * @queryParam model_id integer Filtra pelos registros relacionados ao identificador informado. Example: 21
     * @queryParam action string Ação registrada no log (create, update ou delete). Example: update
     * @queryParam user_id integer Identificador do usuário responsável pela ação. Example: 5
     * @queryParam from date Considerar registros a partir desta data (YYYY-MM-DD). Example: 2024-07-01
     * @queryParam to date Considerar registros até esta data (YYYY-MM-DD). Example: 2024-07-15
     * @queryParam per_page integer Quantidade de registros por página (1-100). Example: 20
     *
     * @responseField data[].id integer Identificador do log.
     * @responseField data[].action string Ação executada.
     * @responseField data[].model string Classe do modelo associado.
     * @responseField data[].model_id integer Identificador do registro associado.
     * @responseField data[].data object Payload capturado no log.
     * @responseField data[].user_id integer|null Usuário responsável pela ação (quando disponível).
     * @responseField data[].created_at string Data de criação do log (ISO 8601).
     * @responseField data[].updated_at string Data da última atualização do log (ISO 8601).
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 10,
     *       "action": "update",
     *       "model": "App\\Models\\Produto",
     *       "model_id": 21,
     *       "data": {"preco":{"old":299.9,"new":349.9}},
     *       "user_id": 5,
     *       "created_at": "2024-07-12T08:43:27Z",
     *       "updated_at": "2024-07-12T08:43:27Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/v1/logs?page=1",
     *     "last": "http://localhost/api/v1/logs?page=3",
     *     "prev": null,
     *     "next": "http://localhost/api/v1/logs?page=2"
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 3,
     *     "path": "http://localhost/api/v1/logs",
     *     "per_page": 20,
     *     "to": 20,
     *     "total": 50
     *   }
     * }
     */
    public function index(ListLogRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = collect($validated)
            ->except(['per_page'])
            ->filter(static fn ($value) => $value !== null && $value !== '')
            ->all();

        $perPage = (int) ($validated['per_page'] ?? $request->query('per_page', 15));
        if ($perPage <= 0) {
            $perPage = 15;
        }

        $logs = $this->service->list($filters, $perPage);

        return LogResource::collection($logs)->response();
    }
}
