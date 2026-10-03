<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One immutable entry in the stock ledger. Never updated or deleted; a
 * correction is a new movement.
 */
class StockMovement extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['ingredient_id', 'quantity', 'type', 'source_type', 'source_id'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'type' => StockMovementType::class,
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Ingredient, $this> */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /** @return MorphTo<Model, $this> */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
