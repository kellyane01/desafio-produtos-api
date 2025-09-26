<?php

namespace App\Http\Requests\Produto;

use Illuminate\Foundation\Http\FormRequest;

class ListProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'categorias' => ['nullable', 'array'],
            'categorias.*' => ['string', 'max:255'],
            'min_preco' => ['nullable', 'numeric', 'min:0'],
            'max_preco' => ['nullable', 'numeric', 'min:0'],
            'disponivel' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string', 'in:nome,preco,categoria,estoque,created_at'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $categorias = $this->input('categorias');

        if (is_string($categorias)) {
            $exploded = array_filter(array_map('trim', explode(',', $categorias)));

            $this->merge([
                'categorias' => array_values($exploded),
            ]);
        }

        if ($this->has('disponivel')) {
            $parsed = filter_var($this->input('disponivel'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($parsed !== null) {
                $this->merge([
                    'disponivel' => $parsed,
                ]);
            }
        }
    }

    public function queryParameters(): array
    {
        return [
            'search' => [
                'description' => 'Busca textual por nome, descrição ou categoria.',
                'example' => 'smartphone',
            ],
            'categoria' => [
                'description' => 'Filtra por uma categoria específica.',
                'example' => 'Eletrônicos',
            ],
            'categorias' => [
                'description' => 'Permite filtrar por múltiplas categorias, aceitando array ou valores separados por vírgula.',
                'example' => ['Eletrônicos', 'Informática'],
            ],
            'min_preco' => [
                'description' => 'Limite mínimo de preço dos produtos retornados.',
                'example' => 100.9,
            ],
            'max_preco' => [
                'description' => 'Limite máximo de preço dos produtos retornados.',
                'example' => 999.9,
            ],
            'disponivel' => [
                'description' => 'Define se apenas produtos com estoque (>0) ou esgotados (<=0) devem ser retornados.',
                'example' => true,
            ],
            'sort' => [
                'description' => 'Campo utilizado na ordenação (nome, preco, categoria, estoque, created_at).',
                'example' => 'preco',
            ],
            'order' => [
                'description' => 'Direção da ordenação (asc ou desc).',
                'example' => 'desc',
            ],
            'page' => [
                'description' => 'Número da página a ser retornada.',
                'example' => 2,
            ],
            'per_page' => [
                'description' => 'Quantidade de registros por página (1-100).',
                'example' => 25,
            ],
        ];
    }
}
