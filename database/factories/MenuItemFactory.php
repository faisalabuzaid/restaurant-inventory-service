<?php

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
        ];
    }

    /**
     * Attach a recipe: [Ingredient => quantity, ...] keyed by ingredient id.
     *
     * @param  array<int, numeric>  $quantitiesByIngredientId
     */
    public function withRecipe(array $quantitiesByIngredientId): static
    {
        return $this->afterCreating(function (MenuItem $item) use ($quantitiesByIngredientId) {
            foreach ($quantitiesByIngredientId as $ingredientId => $quantity) {
                $item->recipeLines()->create([
                    'ingredient_id' => $ingredientId,
                    'quantity' => $quantity,
                ]);
            }
        });
    }
}
