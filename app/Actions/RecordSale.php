<?php

namespace App\Actions;

use App\Enums\StockMovementType;
use App\Exceptions\InvalidOperation;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\RecipeLine;
use App\Models\Sale;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Apply a POS sale event to stock.
 *
 * A sale is a fact that already happened at the till, so it is never
 * rejected for lack of stock: every recipe line produces a negative movement
 * and, if an ingredient ends up below zero, the result carries a warning.
 * Negative stock is a signal that the storeroom and the records disagree.
 *
 * Rejected only when the event itself is invalid: a menu item with no
 * recipe (422 empty_recipe). An optional idempotency key makes POS retries
 * safe: a repeated key returns the original sale without touching stock.
 */
class RecordSale
{
    public function __invoke(
        MenuItem $menuItem,
        int $quantity,
        ?string $idempotencyKey = null,
        ?CarbonInterface $soldAt = null,
    ): SaleResult {
        return DB::transaction(function () use ($menuItem, $quantity, $idempotencyKey, $soldAt) {
            if ($idempotencyKey !== null) {
                $existing = Sale::query()->where('idempotency_key', $idempotencyKey)->first();

                if ($existing !== null) {
                    return new SaleResult($existing, consumed: [], warnings: [], replayed: true);
                }
            }

            $recipe = $menuItem->recipeLines()->with('ingredient')->get();

            if ($recipe->isEmpty()) {
                throw new InvalidOperation(
                    "Menu item '{$menuItem->name}' has no recipe, so a sale cannot be applied to stock.",
                    errorCode: 'empty_recipe',
                    context: ['menu_item_id' => $menuItem->id],
                );
            }

            // Lock the affected ingredients so concurrent sales serialise on
            // real databases; read their current stock in the same statement.
            $stockBefore = Ingredient::query()
                ->whereIn('id', $recipe->pluck('ingredient_id'))
                ->lockForUpdate()
                ->withCurrentStock()
                ->get()
                ->mapWithKeys(fn (Ingredient $i) => [$i->id => $i->currentStock()]);

            $sale = Sale::create([
                'menu_item_id' => $menuItem->id,
                'quantity' => $quantity,
                'idempotency_key' => $idempotencyKey,
                'sold_at' => $soldAt ?? now(),
            ]);

            $consumed = [];
            $warnings = [];

            foreach ($recipe as $line) {
                /** @var RecipeLine $line */
                $used = bcmul($line->quantity, (string) $quantity, 3);
                $after = bcsub($stockBefore[$line->ingredient_id], $used, 3);

                $sale->stockMovements()->create([
                    'ingredient_id' => $line->ingredient_id,
                    'quantity' => bcmul($used, '-1', 3),
                    'type' => StockMovementType::Sale,
                ]);

                $entry = [
                    'ingredient_id' => $line->ingredient_id,
                    'ingredient_name' => $line->ingredient->name,
                    'unit' => $line->ingredient->unit->value,
                    'quantity' => (float) $used,
                    'stock_after' => (float) $after,
                ];
                $consumed[] = $entry;

                if (bccomp($after, '0', 3) < 0) {
                    $warnings[] = [
                        'ingredient_id' => $line->ingredient_id,
                        'ingredient_name' => $line->ingredient->name,
                        'unit' => $line->ingredient->unit->value,
                        'stock_after' => (float) $after,
                    ];
                }
            }

            return new SaleResult($sale, $consumed, $warnings);
        });
    }
}
