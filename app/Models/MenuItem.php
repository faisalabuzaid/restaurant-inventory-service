<?php

namespace App\Models;

use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    /** @use HasFactory<MenuItemFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    /** @return HasMany<RecipeLine, $this> */
    public function recipeLines(): HasMany
    {
        return $this->hasMany(RecipeLine::class);
    }

    /**
     * Replace the whole recipe atomically.
     *
     * @param  array<int, array{ingredient_id: int, quantity: numeric}>  $lines
     */
    public function replaceRecipe(array $lines): void
    {
        $this->recipeLines()->delete();
        $this->recipeLines()->createMany($lines);
    }
}
