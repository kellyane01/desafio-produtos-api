<?php

namespace App\Providers;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;

class ElasticsearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            $config = config('elasticsearch');

            $builder = ClientBuilder::create()
                ->setHosts($config['hosts'])
                ->setRetries($config['retries']);

            if (! empty($config['username']) && ! empty($config['password'])) {
                $builder->setBasicAuthentication($config['username'], $config['password']);
            }

            return $builder->build();
        });
    }
}
