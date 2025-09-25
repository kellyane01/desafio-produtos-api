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
}
