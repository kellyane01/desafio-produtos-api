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
}
