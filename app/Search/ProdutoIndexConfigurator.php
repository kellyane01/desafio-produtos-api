<?php

namespace App\Search;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Elastic\Transport\Exception\NoNodeAvailableException;
use Illuminate\Support\Facades\Log;

class ProdutoIndexConfigurator
{
    private bool $checked = false;

    public function __construct(private readonly Client $client)
    {
    }

    public function indexName(): string
    {
        return config('elasticsearch.index');
    }

    public function ensureExists(): void
    {
        if ($this->checked) {
            return;
        }

        if ($this->exists()) {
            $this->checked = true;

            return;
        }

        $this->checked = $this->create();
    }

    public function exists(): bool
    {
        try {
            $response = $this->client->indices()->exists(['index' => $this->indexName()]);
        } catch (NoNodeAvailableException) {
            Log::warning('Não foi possível verificar a existência do índice no Elasticsearch: nenhum nó disponível.');

            return false;
        }

        return $this->asBool($response);
    }

    public function recreate(): void
    {
        $this->delete();
        $this->checked = $this->create();
    }

    public function delete(): void
    {
        if (! $this->exists()) {
            return;
        }

        try {
            $this->client->indices()->delete(['index' => $this->indexName()]);
        } catch (ClientResponseException $exception) {
            if ($exception->getCode() !== 404) {
                throw $exception;
            }
        } catch (NoNodeAvailableException) {
            Log::warning('Unable to delete Elasticsearch index; no node available.');
        }
    }

    public function create(): bool
    {
        $settings = [
            'index' => $this->indexName(),
            'body' => [
                'settings' => $this->settings(),
                'mappings' => $this->mappings(),
            ],
        ];

        try {
            $this->client->indices()->create($settings);
        } catch (ClientResponseException $exception) {
            if ($exception->getCode() === 400 && str_contains($exception->getMessage(), 'resource_already_exists')) {
                return true;
            }

            throw $exception;
        } catch (NoNodeAvailableException) {
            Log::warning('Unable to create Elasticsearch index; no node available.');

            return false;
        }

        return true;
    }

    /**
     * @param Elasticsearch|bool $response
     */
    private function asBool(Elasticsearch|bool $response): bool
    {
        if (is_bool($response)) {
            return $response;
        }

        return $response->asBool();
    }

    /**
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        return [
            'analysis' => [
                'analyzer' => [
                    'produto_pt' => [
                        'tokenizer' => 'standard',
                        'filter' => [
                            'lowercase',
                            'asciifolding',
                            'portuguese_stop',
                            'portuguese_stemmer',
                        ],
                    ],
                ],
                'filter' => [
                    'portuguese_stop' => [
                        'type' => 'stop',
                        'stopwords' => '_portuguese_',
                    ],
                    'portuguese_stemmer' => [
                        'type' => 'stemmer',
                        'language' => 'light_portuguese',
                    ],
                ],
                'normalizer' => [
                    'lowercase_normalizer' => [
                        'type' => 'custom',
                        'filter' => ['lowercase', 'asciifolding'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mappings(): array
    {
        return [
            'dynamic' => false,
            'properties' => [
                'id' => ['type' => 'keyword'],
                'nome' => [
                    'type' => 'text',
                    'analyzer' => 'produto_pt',
                    'fields' => [
                        'raw' => ['type' => 'keyword', 'ignore_above' => 256],
                    ],
                ],
                'nome_sort' => [
                    'type' => 'keyword',
                    'normalizer' => 'lowercase_normalizer',
                ],
                'descricao' => [
                    'type' => 'text',
                    'analyzer' => 'produto_pt',
                ],
                'categoria' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                ],
                'categoria_terms' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                ],
                'preco' => ['type' => 'double'],
                'estoque' => ['type' => 'integer'],
                'disponivel' => ['type' => 'boolean'],
                'created_at' => ['type' => 'date', 'format' => 'strict_date_optional_time'],
                'updated_at' => ['type' => 'date', 'format' => 'strict_date_optional_time'],
                'nome_suggest' => ['type' => 'completion'],
            ],
        ];
    }
}
