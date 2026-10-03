<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesQuantityLines;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRecipeRequest extends FormRequest
{
    use ValidatesQuantityLines;

    public function rules(): array
    {
        return $this->ingredientLineRules();
    }

    public function messages(): array
    {
        return $this->ingredientLineMessages();
    }
}
