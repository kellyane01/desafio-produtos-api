<?php

namespace App\Search;

use App\Models\Produto;
use Elastic\Elasticsearch\ClientInterface;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Transport\Exception\NoNodeAvailableException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ProdutoIndexer
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly ProdutoIndexConfigurator $configurator,
    ) {}

    public function indexById(int $produtoId): void
    {
        $produto = Produto::query()->find($produtoId);

        if ($produto === null) {
            $this->delete($produtoId);

            return;
        }

        $this->index($produto);
    }

    public function index(Produto $produto): void
    {
        $this->configurator->ensureExists();

        $document = ProdutoSearchDocument::fromModel($produto);

        try {
            $this->client->index([
                'index' => $this->configurator->indexName(),
                'id' => (string) $produto->getKey(),
                'body' => $document,
                'refresh' => false,
            ]);
        } catch (NoNodeAvailableException) {
            Log::warning('Não foi possível sincronizar produto com Elasticsearch: nenhum nó disponível.', [
                'produto_id' => $produto->getKey(),
            ]);
        }
    }

    /**
     * @param  Collection<int, Produto>  $produtos
     */
    public function bulk(Collection $produtos): void
    {
        if ($produtos->isEmpty()) {
            return;
        }

        $this->configurator->ensureExists();

        $body = [];

        foreach ($produtos as $produto) {
            $body[] = [
                'index' => [
                    '_index' => $this->configurator->indexName(),
                    '_id' => (string) $produto->getKey(),
                ],
            ];
            $body[] = ProdutoSearchDocument::fromModel($produto);
        }

        try {
            $this->client->bulk([
                'refresh' => false,
                'body' => $body,
            ]);
        } catch (NoNodeAvailableException) {
            Log::warning('Não foi possível sincronizar produtos em lote com Elasticsearch: nenhum nó disponível.');
        }
    }

    public function delete(int $produtoId): void
    {
        if (! $this->configurator->exists()) {
            return;
        }

        try {
            $this->client->delete([
                'index' => $this->configurator->indexName(),
                'id' => (string) $produtoId,
            ]);
        } catch (ClientResponseException $exception) {
            if ($exception->getCode() !== 404) {
                throw $exception;
            }
        } catch (NoNodeAvailableException) {
            Log::warning('Não foi possível remover produto do Elasticsearch: nenhum nó disponível.', [
                'produto_id' => $produtoId,
            ]);
        }
    }

    public function recreateIndex(): void
    {
        $this->configurator->recreate();
    }

    public function ensureIndex(): void
    {
        $this->configurator->ensureExists();
    }
}
