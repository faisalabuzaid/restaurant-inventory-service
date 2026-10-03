<?php

use App\Enums\Unit;
use App\Models\Ingredient;
use App\Models\MenuItem;

beforeEach(function () {
    $this->beef = Ingredient::factory()->unit(Unit::Gram)->create(['name' => 'Beef']);
    $this->bun = Ingredient::factory()->unit(Unit::Piece)->create(['name' => 'Bun']);
    $this->cheese = Ingredient::factory()->unit(Unit::Gram)->create(['name' => 'Cheese']);
});

it('creates a menu item with its recipe', function () {
    $response = $this->postJson('/api/menu-items', [
        'name' => 'Classic Burger',
        'lines' => [
            ['ingredient_id' => $this->beef->id, 'quantity' => 150],
            ['ingredient_id' => $this->bun->id, 'quantity' => 1],
            ['ingredient_id' => $this->cheese->id, 'quantity' => 20],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Classic Burger')
        ->assertJsonCount(3, 'data.recipe')
        ->assertJsonPath('data.recipe.0.ingredient_name', 'Beef')
        ->assertJsonPath('data.recipe.0.quantity', 150)
        ->assertJsonPath('data.recipe.0.unit', 'g');

    $this->assertDatabaseCount('recipe_lines', 3);
});

it('lists menu items with their recipes', function () {
    MenuItem::factory()->withRecipe([$this->beef->id => 150, $this->bun->id => 1])->create(['name' => 'Classic Burger']);
    MenuItem::factory()->withRecipe([$this->bun->id => 1])->create(['name' => 'Bun Only']);

    $this->getJson('/api/menu-items')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Bun Only')
        ->assertJsonCount(2, 'data.1.recipe');
});

it('replaces the recipe atomically', function () {
    $item = MenuItem::factory()->withRecipe([$this->beef->id => 150, $this->bun->id => 1])->create();

    $this->putJson("/api/menu-items/{$item->id}/recipe", [
        'lines' => [
            ['ingredient_id' => $this->beef->id, 'quantity' => 200],
            ['ingredient_id' => $this->cheese->id, 'quantity' => 25.5],
        ],
    ])->assertOk()
        ->assertJsonCount(2, 'data.recipe');

    expect($item->recipeLines()->pluck('quantity', 'ingredient_id')->map(fn ($q) => (float) $q)->all())
        ->toBe([$this->beef->id => 200.0, $this->cheese->id => 25.5]);
});

it('requires at least one recipe line', function () {
    $this->postJson('/api/menu-items', ['name' => 'Air Burger', 'lines' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lines']);
});

it('rejects a non-positive quantity', function () {
    $this->postJson('/api/menu-items', [
        'name' => 'Classic Burger',
        'lines' => [['ingredient_id' => $this->beef->id, 'quantity' => 0]],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['lines.0.quantity']);
});

it('rejects more than three decimal places', function () {
    $this->postJson('/api/menu-items', [
        'name' => 'Classic Burger',
        'lines' => [['ingredient_id' => $this->beef->id, 'quantity' => 0.0001]],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['lines.0.quantity']);
});

it('rejects an unknown ingredient', function () {
    $this->postJson('/api/menu-items', [
        'name' => 'Classic Burger',
        'lines' => [['ingredient_id' => 999, 'quantity' => 1]],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['lines.0.ingredient_id']);
});

it('rejects the same ingredient twice in one recipe', function () {
    $this->postJson('/api/menu-items', [
        'name' => 'Classic Burger',
        'lines' => [
            ['ingredient_id' => $this->beef->id, 'quantity' => 100],
            ['ingredient_id' => $this->beef->id, 'quantity' => 50],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['lines.0.ingredient_id']);
});

it('rejects a duplicate menu item name', function () {
    MenuItem::factory()->create(['name' => 'Classic Burger']);

    $this->postJson('/api/menu-items', [
        'name' => 'Classic Burger',
        'lines' => [['ingredient_id' => $this->beef->id, 'quantity' => 1]],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});
