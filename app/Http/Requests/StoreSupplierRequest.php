<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:suppliers,name'],
            'contact' => ['nullable', 'string', 'max:255'],
        ];
    }
}
