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
}
