<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
            'sold_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'menu_item_id.exists' => 'Unknown menu item.',
            'quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}
