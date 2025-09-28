<?php

namespace App\Providers;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientInterface;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;

class ElasticsearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ClientInterface::class, function () {
            $config = config('elasticsearch');

            $builder = ClientBuilder::create()
                ->setHosts($config['hosts'])
                ->setRetries($config['retries']);

            if (! empty($config['username']) && ! empty($config['password'])) {
                $builder->setBasicAuthentication($config['username'], $config['password']);
            }

            return $builder->build();
        });

        $this->app->alias(ClientInterface::class, Client::class);
    }
}
