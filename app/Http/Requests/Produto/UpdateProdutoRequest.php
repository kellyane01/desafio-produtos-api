<?php

namespace App\Http\Requests\Produto;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'descricao' => ['sometimes', 'required', 'string'],
            'preco' => ['sometimes', 'required', 'numeric', 'min:0'],
            'categoria' => ['sometimes', 'required', 'string', 'max:255'],
            'estoque' => ['sometimes', 'required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do produto não pode ficar em branco quando informado.',
            'nome.string' => 'O nome do produto deve ser um texto.',
            'nome.max' => 'O nome do produto deve ter no máximo :max caracteres.',
            'descricao.required' => 'A descrição do produto não pode ficar em branco quando informada.',
            'descricao.string' => 'A descrição do produto deve ser um texto.',
            'preco.required' => 'O preço do produto não pode ficar em branco quando informado.',
            'preco.numeric' => 'O preço do produto deve ser um número válido.',
            'preco.min' => 'O preço do produto deve ser maior ou igual a :min.',
            'categoria.required' => 'A categoria do produto não pode ficar em branco quando informada.',
            'categoria.string' => 'A categoria do produto deve ser um texto.',
            'categoria.max' => 'A categoria do produto deve ter no máximo :max caracteres.',
            'estoque.required' => 'O estoque não pode ficar em branco quando informado.',
            'estoque.integer' => 'O estoque deve ser um número inteiro.',
            'estoque.min' => 'O estoque não pode ser negativo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nome' => 'nome do produto',
            'descricao' => 'descrição do produto',
            'preco' => 'preço do produto',
            'categoria' => 'categoria do produto',
            'estoque' => 'estoque do produto',
        ];
    }
}
