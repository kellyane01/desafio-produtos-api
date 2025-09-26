<?php

namespace Tests\Feature\Produto;

use App\Jobs\LogModelActivity;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProdutoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_requires_authentication_to_access_produtos_routes(): void
    {
        $this->getJson('/api/produtos')->assertUnauthorized();
        $this->postJson('/api/produtos', [])->assertUnauthorized();
        $this->getJson('/api/produtos/1')->assertUnauthorized();
    }

    public function test_index_returns_paginated_products(): void
    {
        $this->authenticate();

        Produto::factory()->count(3)->create();

        $response = $this->getJson('/api/produtos');

        $response
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'nome',
                    'descricao',
                    'preco',
                    'categoria',
                    'estoque',
                    'created_at',
                    'updated_at',
                ]],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_index_applies_search_and_category_filters(): void
    {
        $this->authenticate();

        Produto::factory()->create([
            'nome' => 'Camiseta Azul',
            'descricao' => 'Camiseta de algodao',
            'categoria' => 'Moda',
        ]);

        Produto::factory()->create([
            'nome' => 'Tenis Esportivo',
            'descricao' => 'Ideal para corridas',
            'categoria' => 'Esporte',
        ]);

        Produto::factory()->create([
            'nome' => 'Camiseta Verde',
            'descricao' => 'Modelo casual',
            'categoria' => 'Moda',
        ]);

        $response = $this->getJson('/api/produtos?search=Camiseta&categoria=Moda');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['nome' => 'Camiseta Azul'])
            ->assertJsonFragment(['nome' => 'Camiseta Verde'])
            ->assertJsonMissing(['nome' => 'Tenis Esportivo']);
    }

    public function test_index_filters_by_price_range_and_availability(): void
    {
        $this->authenticate();

        Produto::factory()->create([
            'nome' => 'Produto Economico',
            'preco' => 50,
            'estoque' => 15,
        ]);

        Produto::factory()->create([
            'nome' => 'Produto Limitado',
            'preco' => 150,
            'estoque' => 5,
        ]);

        Produto::factory()->create([
            'nome' => 'Produto Sem Estoque',
            'preco' => 180,
            'estoque' => 0,
        ]);

        $response = $this->getJson('/api/produtos?min_preco=100&max_preco=200&disponivel=true');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['nome' => 'Produto Limitado'])
            ->assertJsonMissing(['nome' => 'Produto Sem Estoque']);
    }

    public function test_index_supports_sorting_by_price_desc(): void
    {
        $this->authenticate();

        Produto::factory()->create(['nome' => 'Produto Bronze', 'preco' => 60]);
        Produto::factory()->create(['nome' => 'Produto Ouro', 'preco' => 200]);
        Produto::factory()->create(['nome' => 'Produto Prata', 'preco' => 150]);

        $response = $this->getJson('/api/produtos?sort=preco&order=desc');

        $response->assertOk();

        $prices = array_map('intval', array_column($response->json('data'), 'preco'));

        $this->assertSame([200, 150, 60], $prices);
    }

    public function test_store_creates_product(): void
    {
        $this->authenticate();
        Bus::fake([LogModelActivity::class]);

        $payload = [
            'nome' => 'Monitor 4K',
            'descricao' => 'Tela UHD para profissionais',
            'preco' => 2499.90,
            'categoria' => 'Eletronicos',
            'estoque' => 8,
        ];

        $response = $this->postJson('/api/produtos', $payload);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'nome' => 'Monitor 4K',
                'categoria' => 'Eletronicos',
            ]);

        $this->assertDatabaseHas('produtos', [
            'nome' => 'Monitor 4K',
            'categoria' => 'Eletronicos',
        ]);
    }

    public function test_store_validates_payload(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/produtos', [
            'nome' => '',
            'descricao' => '',
            'preco' => -10,
            'categoria' => '',
            'estoque' => -5,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nome', 'descricao', 'preco', 'categoria', 'estoque']);
    }

    public function test_show_returns_single_resource(): void
    {
        $this->authenticate();

        $produto = Produto::factory()->create(['nome' => 'Cafeteira Digital']);

        $response = $this->getJson("/api/produtos/{$produto->id}");

        $response
            ->assertOk()
            ->assertJsonFragment(['nome' => 'Cafeteira Digital', 'id' => $produto->id]);
    }

    public function test_update_modifies_product(): void
    {
        $this->authenticate();
        Bus::fake([LogModelActivity::class]);

        $produto = Produto::withoutEvents(fn () => Produto::factory()->create([
            'nome' => 'Smartphone X',
            'estoque' => 5,
        ]));

        $payload = [
            'nome' => 'Smartphone X Pro',
            'estoque' => 15,
        ];

        $response = $this->patchJson("/api/produtos/{$produto->id}", $payload);

        $response
            ->assertOk()
            ->assertJsonFragment(['nome' => 'Smartphone X Pro', 'estoque' => 15]);

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'nome' => 'Smartphone X Pro',
            'estoque' => 15,
        ]);
    }

    public function test_update_validates_payload(): void
    {
        $this->authenticate();

        $produto = Produto::factory()->create(['preco' => 199.90]);

        $response = $this->patchJson("/api/produtos/{$produto->id}", [
            'preco' => -50,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['preco']);

        $produto->refresh();

        $this->assertSame(199.90, (float) $produto->preco);
    }

    public function test_destroy_deletes_product(): void
    {
        $this->authenticate();
        Bus::fake([LogModelActivity::class]);

        $produto = Produto::withoutEvents(fn () => Produto::factory()->create());

        $response = $this->deleteJson("/api/produtos/{$produto->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('produtos', ['id' => $produto->id]);
    }

    private function authenticate(): User
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        return $user;
    }
}
