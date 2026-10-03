<?php

namespace App\Http\Resources;

use App\Models\Delivery;
use App\Models\DeliveryLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Delivery */
class DeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_order_id' => $this->purchase_order_id,
            'received_at' => $this->received_at->toISOString(),
            'note' => $this->note,
            'lines' => $this->lines->map(fn (DeliveryLine $line) => [
                'purchase_order_line_id' => $line->purchase_order_line_id,
                'ingredient_id' => $line->purchaseOrderLine->ingredient_id,
                'ingredient_name' => $line->purchaseOrderLine->ingredient->name,
                'unit' => $line->purchaseOrderLine->ingredient->unit->value,
                'quantity' => (float) $line->quantity,
            ])->values(),
        ];
    }
}
