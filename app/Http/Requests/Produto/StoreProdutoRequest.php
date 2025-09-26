<?php

namespace App\Http\Requests\Produto;

use Illuminate\Foundation\Http\FormRequest;

class StoreProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string'],
            'preco' => ['required', 'numeric', 'min:0'],
            'categoria' => ['required', 'string', 'max:255'],
            'estoque' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do produto.',
            'nome.string' => 'O nome do produto deve ser um texto.',
            'nome.max' => 'O nome do produto deve ter no máximo :max caracteres.',
            'descricao.required' => 'Informe a descrição do produto.',
            'descricao.string' => 'A descrição do produto deve ser um texto.',
            'preco.required' => 'Informe o preço do produto.',
            'preco.numeric' => 'O preço do produto deve ser um número válido.',
            'preco.min' => 'O preço do produto deve ser maior ou igual a :min.',
            'categoria.required' => 'Informe a categoria do produto.',
            'categoria.string' => 'A categoria do produto deve ser um texto.',
            'categoria.max' => 'A categoria do produto deve ter no máximo :max caracteres.',
            'estoque.required' => 'Informe a quantidade em estoque.',
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
