<?php

namespace App\Http\Resources;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PurchaseOrder */
class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->supplier->name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_open' => $this->status->isOpen(),
            'accepts_deliveries' => $this->status->acceptsDeliveries(),
            'notes' => $this->notes,
            'lines' => $this->lines->map(fn (PurchaseOrderLine $line) => [
                'id' => $line->id,
                'ingredient_id' => $line->ingredient_id,
                'ingredient_name' => $line->ingredient->name,
                'unit' => $line->ingredient->unit->value,
                'quantity_ordered' => (float) $line->quantity_ordered,
                'quantity_received' => (float) $line->quantityReceived(),
                'outstanding' => (float) $line->outstanding(),
            ])->values(),
            'deliveries' => DeliveryResource::collection($this->whenLoaded('deliveries')),
            'sent_at' => $this->sent_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
