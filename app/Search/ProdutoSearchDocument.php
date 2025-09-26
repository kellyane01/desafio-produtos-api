<?php

namespace App\Search;

use App\Models\Produto;
use Illuminate\Support\Str;

class ProdutoSearchDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(Produto $produto): array
    {
        $createdAt = $produto->created_at?->toIso8601String();
        $updatedAt = $produto->updated_at?->toIso8601String();

        return [
            'id' => (string) $produto->getKey(),
            'nome' => $produto->nome,
            'nome_sort' => $produto->nome,
            'descricao' => $produto->descricao,
            'categoria' => $produto->categoria,
            'preco' => (float) $produto->preco,
            'estoque' => (int) $produto->estoque,
            'disponivel' => $produto->estoque > 0,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'nome_suggest' => self::buildSuggestion($produto),
            'categoria_terms' => self::normalizeCategoria($produto->categoria),
        ];
    }

    /**
     * @return array{input: array<int, string>, weight: int}
     */
    private static function buildSuggestion(Produto $produto): array
    {
        $inputs = array_filter([
            $produto->nome,
            $produto->categoria,
        ]);

        foreach (self::tokenize($produto->nome) as $token) {
            $inputs[] = $token;
        }

        $inputs = array_values(array_unique(array_filter(array_map('trim', $inputs))));

        $weight = $produto->estoque > 0 ? 10 : 3;

        return [
            'input' => $inputs,
            'weight' => $weight,
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function tokenize(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        $value = Str::of($value)->lower()->squish()->toString();

        return array_filter(explode(' ', $value));
    }

    private static function normalizeCategoria(?string $categoria): ?string
    {
        if ($categoria === null) {
            return null;
        }

        return Str::of($categoria)->lower()->squish()->toString();
    }
}
