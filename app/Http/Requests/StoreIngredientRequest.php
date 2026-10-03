<?php

namespace App\Http\Requests;

use App\Enums\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIngredientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:ingredients,name'],
            'unit' => ['required', Rule::enum(Unit::class)],
        ];
    }
}
