<?php

namespace Tests\Unit\Search;

use App\Models\Produto;
use App\Search\ProdutoSearchDocument;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProdutoSearchDocumentTest extends TestCase
{
    public function test_from_model_builds_expected_document_structure(): void
    {
        $produto = Produto::factory()->make([
            'id' => 50,
            'nome' => 'Mouse Gamer Pro',
            'descricao' => 'Sensor de 16000 DPI',
            'categoria' => 'Perifericos',
            'preco' => 349.90,
            'estoque' => 12,
        ]);

        $produto->created_at = Carbon::parse('2024-01-10 12:00:00');
        $produto->updated_at = Carbon::parse('2024-01-12 08:30:00');

        $document = ProdutoSearchDocument::fromModel($produto);

        $this->assertSame('50', $document['id']);
        $this->assertContains('mouse', $document['nome_suggest']['input']);
        $this->assertSame(10, $document['nome_suggest']['weight']);
        $this->assertSame('Perifericos', $document['categoria']);
        $this->assertSame('perifericos', $document['categoria_terms']);
        $this->assertSame('2024-01-10T12:00:00+00:00', $document['created_at']);
        $this->assertTrue($document['disponivel']);
    }

    public function test_from_model_sets_lower_weight_when_unavailable(): void
    {
        $produto = Produto::factory()->make([
            'id' => 99,
            'nome' => 'Webcam Full HD',
            'categoria' => 'Acessorios',
            'estoque' => 0,
        ]);

        $document = ProdutoSearchDocument::fromModel($produto);

        $this->assertSame(3, $document['nome_suggest']['weight']);
        $this->assertFalse($document['disponivel']);
    }
}
