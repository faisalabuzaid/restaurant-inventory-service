<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesQuantityLines;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    use ValidatesQuantityLines;

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            ...$this->ingredientLineRules(),
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.exists' => 'Unknown supplier.',
            ...$this->ingredientLineMessages(),
        ];
    }
}
