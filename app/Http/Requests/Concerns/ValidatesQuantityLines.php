<?php

namespace App\Http\Requests\Concerns;

/**
 * Shared validation for "one or more ingredient + quantity lines" payloads
 * (recipes, purchase orders). Quantities are positive with at most three
 * decimals, matching the decimal(12,3) columns.
 */
trait ValidatesQuantityLines
{
    /** @return array<string, array<int, mixed>> */
    protected function ingredientLineRules(string $prefix = 'lines'): array
    {
        return [
            $prefix => ['required', 'array', 'min:1'],
            "$prefix.*.ingredient_id" => ['required', 'integer', 'distinct', 'exists:ingredients,id'],
            "$prefix.*.quantity" => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:999999999'],
        ];
    }

    /** @return array<string, string> */
    protected function ingredientLineMessages(string $prefix = 'lines'): array
    {
        return [
            "$prefix.required" => 'At least one line is required.',
            "$prefix.min" => 'At least one line is required.',
            "$prefix.*.ingredient_id.distinct" => 'Each ingredient may appear only once.',
            "$prefix.*.ingredient_id.exists" => 'Unknown ingredient.',
            "$prefix.*.quantity.gt" => 'Quantity must be greater than zero.',
        ];
    }
}
