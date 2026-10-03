<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesQuantityLines;
use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
{
    use ValidatesQuantityLines;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:menu_items,name'],
            ...$this->ingredientLineRules(),
        ];
    }

    public function messages(): array
    {
        return $this->ingredientLineMessages();
    }
}
