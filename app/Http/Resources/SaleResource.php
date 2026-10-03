<?php

namespace App\Http\Resources;

use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Sale */
class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menu_item_id' => $this->menu_item_id,
            'menu_item_name' => $this->menuItem->name,
            'quantity' => $this->quantity,
            'idempotency_key' => $this->idempotency_key,
            'sold_at' => $this->sold_at->toISOString(),
            'movements' => $this->whenLoaded('stockMovements', fn () => $this->stockMovements->map(fn (StockMovement $m) => [
                'ingredient_id' => $m->ingredient_id,
                'ingredient_name' => $m->ingredient->name,
                'unit' => $m->ingredient->unit->value,
                'quantity' => (float) $m->quantity,
            ])->values()),
        ];
    }
}
