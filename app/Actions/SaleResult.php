<?php

namespace App\Actions;

use App\Models\Sale;

/**
 * Outcome of RecordSale: the persisted sale, what it consumed (with the
 * resulting stock level per ingredient) and any negative-stock warnings.
 */
final readonly class SaleResult
{
    /**
     * @param  list<array{ingredient_id: int, ingredient_name: string, unit: string, quantity: float, stock_after: float}>  $consumed
     * @param  list<array{ingredient_id: int, ingredient_name: string, unit: string, stock_after: float}>  $warnings
     */
    public function __construct(
        public Sale $sale,
        public array $consumed,
        public array $warnings,
        public bool $replayed = false,
    ) {}
}
