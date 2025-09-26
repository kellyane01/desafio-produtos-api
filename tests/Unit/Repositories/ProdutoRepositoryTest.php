<?php

namespace Tests\Unit\Repositories;

use App\Models\Produto;
use App\Repositories\ProdutoRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProdutoRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_paginate_filters_by_search_term_and_category(): void
    {
        $camisetaAzul = Produto::withoutEvents(fn () => Produto::factory()->create([
            'nome' => 'Camiseta Azul',
            'descricao' => 'Modelo casual com gola V',
            'categoria' => 'Moda',
        ]));

        Produto::withoutEvents(fn () => Produto::factory()->create([
            'nome' => 'Tenis Corrida',
            'descricao' => 'Ideal para corridas de rua',
            'categoria' => 'Esporte',
        ]));

        Produto::withoutEvents(fn () => Produto::factory()->create([
            'nome' => 'Camiseta Verde',
            'descricao' => 'Modelo esportivo',
            'categoria' => 'Moda',
        ]));

        $result = $this->repository()->paginate([
            'search' => 'Camiseta Azul',
            'categoria' => 'Moda',
        ], 10);

        $ids = collect($result->items())->map(fn (Produto $produto) => $produto->getKey())->all();

        $this->assertSame([$camisetaAzul->getKey()], $ids);
        $this->assertSame(1, $result->total());
    }

    public function test_paginate_filters_by_price_range_and_availability(): void
    {
        Produto::withoutEvents(fn () => Produto::factory()->create([
            'nome' => 'Produto Basico',
            'preco' => 80,
            'estoque' => 0,
        ]));

        $produtoEsperado = Produto::withoutEvents(fn () => Produto::factory()->create([
            'nome' => 'Produto Premium',
            'preco' => 150,
            'estoque' => 12,
        ]));

        Produto::withoutEvents(fn () => Produto::factory()->create([
            'nome' => 'Produto de Luxo',
            'preco' => 320,
            'estoque' => 2,
        ]));

        $result = $this->repository()->paginate([
            'min_preco' => 120,
            'max_preco' => 200,
            'disponivel' => true,
        ], 10);

        $ids = collect($result->items())->map(fn (Produto $produto) => $produto->getKey())->all();

        $this->assertSame([$produtoEsperado->getKey()], $ids);
        $this->assertSame(1, $result->total());
    }

    public function test_paginate_respects_sorting_and_page_selection(): void
    {
        $produtos = [
            Produto::withoutEvents(fn () => Produto::factory()->create(['nome' => 'Produto Bronze', 'preco' => 100])),
            Produto::withoutEvents(fn () => Produto::factory()->create(['nome' => 'Produto Prata', 'preco' => 200])),
            Produto::withoutEvents(fn () => Produto::factory()->create(['nome' => 'Produto Ouro', 'preco' => 400])),
            Produto::withoutEvents(fn () => Produto::factory()->create(['nome' => 'Produto Platina', 'preco' => 300])),
        ];

        $result = $this->repository()->paginate([
            'sort' => 'preco',
            'order' => 'desc',
            'page' => 2,
        ], 2);

        $this->assertSame(4, $result->total());
        $this->assertSame(2, $result->perPage());
        $this->assertSame(2, $result->currentPage());

        $precosOrdenados = collect($result->items())
            ->map(fn (Produto $produto) => (float) $produto->preco)
            ->all();

        $this->assertSame([200.0, 100.0], $precosOrdenados);
        $this->assertSame([
            $produtos[1]->getKey(),
            $produtos[0]->getKey(),
        ], collect($result->items())->map(fn (Produto $produto) => $produto->getKey())->all());
    }

    private function repository(): ProdutoRepositoryInterface
    {
        return app(ProdutoRepositoryInterface::class);
    }
}
