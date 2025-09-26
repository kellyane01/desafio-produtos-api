<?php

namespace App\Http\Requests\Log;

use Illuminate\Foundation\Http\FormRequest;

class ListLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|int>>
     */
    public function rules(): array
    {
        return [
            'model' => ['nullable', 'string', 'max:255'],
            'model_id' => ['nullable', 'integer', 'min:1'],
            'action' => ['nullable', 'string', 'in:create,update,delete'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function queryParameters(): array
    {
        return [
            'model' => [
                'description' => 'Nome completo (FQCN) do modelo monitorado.',
                'example' => 'App\\Models\\Produto',
            ],
            'model_id' => [
                'description' => 'Filtra pelos registros relacionados ao identificador informado.',
                'example' => 21,
            ],
            'action' => [
                'description' => 'Tipo de ação executada (create, update ou delete).',
                'example' => 'update',
            ],
            'user_id' => [
                'description' => 'Identificador do usuário responsável pela ação.',
                'example' => 5,
            ],
            'from' => [
                'description' => 'Considerar registros a partir desta data (YYYY-MM-DD).',
                'example' => '2024-07-01',
            ],
            'to' => [
                'description' => 'Considerar registros até esta data (YYYY-MM-DD).',
                'example' => '2024-07-15',
            ],
            'per_page' => [
                'description' => 'Quantidade de registros por página (1-100).',
                'example' => 20,
            ],
        ];
    }
}
