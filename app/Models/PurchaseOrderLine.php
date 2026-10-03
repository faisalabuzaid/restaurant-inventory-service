<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderLine extends Model
{
    protected $fillable = ['ingredient_id', 'quantity_ordered'];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'decimal:3',
        ];
    }

    /** @return BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /** @return BelongsTo<Ingredient, $this> */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /** @return HasMany<DeliveryLine, $this> */
    public function deliveryLines(): HasMany
    {
        return $this->hasMany(DeliveryLine::class);
    }

    /**
     * Eager-load the received total as `quantity_received`.
     *
     * @param  Builder<PurchaseOrderLine>  $query
     */
    public function scopeWithQuantityReceived(Builder $query): void
    {
        $query->withSum('deliveryLines as quantity_received', 'quantity');
    }

    /** Received so far = SUM of delivery lines, never stored. 3-decimal string. */
    public function quantityReceived(): string
    {
        $sum = array_key_exists('quantity_received', $this->attributes)
            ? $this->attributes['quantity_received']
            : $this->deliveryLines()->sum('quantity');

        return number_format((float) ($sum ?? 0), 3, '.', '');
    }

    /** Ordered minus received. 3-decimal string, never below zero by construction. */
    public function outstanding(): string
    {
        return bcsub($this->quantity_ordered, $this->quantityReceived(), 3);
    }

    public function isFullyReceived(): bool
    {
        return bccomp($this->outstanding(), '0', 3) <= 0;
    }
}
