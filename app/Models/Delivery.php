<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A physical delivery recorded against a purchase order (all or part of it). */
class Delivery extends Model
{
    protected $fillable = ['purchase_order_id', 'received_at', 'note'];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /** @return HasMany<DeliveryLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(DeliveryLine::class);
    }
}
