<?php

namespace App\Http\Resources;

use App\Models\MenuItem;
use App\Models\RecipeLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MenuItem */
class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'recipe' => $this->recipeLines->map(fn (RecipeLine $line) => [
                'ingredient_id' => $line->ingredient_id,
                'ingredient_name' => $line->ingredient->name,
                'unit' => $line->ingredient->unit->value,
                'quantity' => (float) $line->quantity,
            ])->values(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
