<?php

namespace App\Http\Controllers\Api;

use App\Enums\PurchaseOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\PurchaseOrderLine;
use Illuminate\Http\JsonResponse;

class StockController extends Controller
{
    /**
     * Current stock per ingredient, straight from the ledger, plus what is
     * still expected from suppliers (outstanding on sent / partially
     * received orders). `meta.generated_at` lets the UI show how fresh the
     * numbers are.
     */
    public function index(): JsonResponse
    {
        $ingredients = Ingredient::query()->withCurrentStock()->orderBy('name')->get();

        $onOrder = PurchaseOrderLine::query()
            ->whereHas('purchaseOrder', fn ($query) => $query->whereIn('status', [
                PurchaseOrderStatus::Sent,
                PurchaseOrderStatus::Received,
            ]))
            ->withQuantityReceived()
            ->get()
            ->groupBy('ingredient_id')
            ->map(fn ($lines) => $lines->reduce(
                fn (string $carry, PurchaseOrderLine $line) => bcadd($carry, $line->outstanding(), 3),
                '0.000',
            ));

        return response()->json([
            'data' => $ingredients->map(function (Ingredient $ingredient) use ($onOrder) {
                $stock = $ingredient->currentStock();

                return [
                    'ingredient_id' => $ingredient->id,
                    'name' => $ingredient->name,
                    'unit' => $ingredient->unit->value,
                    'current_stock' => (float) $stock,
                    'is_negative' => bccomp($stock, '0', 3) < 0,
                    'on_order' => (float) ($onOrder[$ingredient->id] ?? '0.000'),
                ];
            })->values(),
            'meta' => [
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }
}
