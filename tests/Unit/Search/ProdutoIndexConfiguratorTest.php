<?php

namespace Tests\Unit\Search;

use App\Search\ProdutoIndexConfigurator;
use Elastic\Elasticsearch\ClientInterface;
use Mockery;
use Tests\TestCase;

class ProdutoIndexConfiguratorTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    public function test_ensure_exists_checks_once_when_index_is_available(): void
    {
        config(['elasticsearch.index' => 'produtos_test']);

        $indices = Mockery::mock();
        $indices->shouldReceive('exists')->once()->with(['index' => 'produtos_test'])->andReturn(true);
        $indices->shouldReceive('create')->never();

        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('indices')->andReturn($indices);

        $configurator = new ProdutoIndexConfigurator($client);
        $configurator->ensureExists();
        $configurator->ensureExists();
    }

    public function test_ensure_exists_creates_index_when_missing(): void
    {
        config(['elasticsearch.index' => 'produtos_missing']);

        $indices = Mockery::mock();
        $indices->shouldReceive('exists')->once()->andReturn(false);
        $indices->shouldReceive('create')->once()->andReturn(true);

        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('indices')->andReturn($indices);

        $configurator = new ProdutoIndexConfigurator($client);
        $configurator->ensureExists();
    }

    public function test_recreate_forces_delete_and_create(): void
    {
        config(['elasticsearch.index' => 'produtos_recreate']);

        $indices = Mockery::mock();
        $indices->shouldReceive('exists')->once()->andReturn(true);
        $indices->shouldReceive('delete')->once()->with(['index' => 'produtos_recreate']);
        $indices->shouldReceive('create')->once()->andReturn(true);

        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('indices')->andReturn($indices);

        $configurator = new ProdutoIndexConfigurator($client);
        $configurator->recreate();
    }

    public function test_delete_noops_when_index_does_not_exist(): void
    {
        config(['elasticsearch.index' => 'produtos_delete']);

        $indices = Mockery::mock();
        $indices->shouldReceive('exists')->once()->andReturn(false);
        $indices->shouldReceive('delete')->never();

        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('indices')->andReturn($indices);

        $configurator = new ProdutoIndexConfigurator($client);
        $configurator->delete();
    }
}
