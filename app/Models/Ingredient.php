<?php

namespace App\Models;

use App\Enums\Unit;
use Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    /** @use HasFactory<IngredientFactory> */
    use HasFactory;

    protected $fillable = ['name', 'unit'];

    protected function casts(): array
    {
        return [
            'unit' => Unit::class,
        ];
    }

    /** @return HasMany<StockMovement, $this> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Eager-load the ledger total as `current_stock` so lists do not query
     * per row.
     *
     * @param  Builder<Ingredient>  $query
     */
    public function scopeWithCurrentStock(Builder $query): void
    {
        $query->withSum('stockMovements as current_stock', 'quantity');
    }

    /**
     * Current stock = SUM of all movements. Uses the eager-loaded value when
     * present, otherwise queries the ledger. Returned as a 3-decimal string
     * so callers can do exact bcmath comparisons.
     */
    public function currentStock(): string
    {
        $sum = array_key_exists('current_stock', $this->attributes)
            ? $this->attributes['current_stock']
            : $this->stockMovements()->sum('quantity');

        return number_format((float) ($sum ?? 0), 3, '.', '');
    }
}
