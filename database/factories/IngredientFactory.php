<?php

namespace Database\Factories;

use App\Enums\Unit;
use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    protected $model = Ingredient::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'unit' => fake()->randomElement(Unit::cases()),
        ];
    }

    public function unit(Unit $unit): static
    {
        return $this->state(['unit' => $unit]);
    }
}
