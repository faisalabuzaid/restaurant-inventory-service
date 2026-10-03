<?php

namespace App\Models;

use App\Enums\Unit;
use Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
