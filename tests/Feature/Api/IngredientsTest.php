<?php

use App\Models\Ingredient;

it('creates an ingredient with a name and unit', function () {
    $response = $this->postJson('/api/ingredients', ['name' => 'Beef', 'unit' => 'g']);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Beef')
        ->assertJsonPath('data.unit', 'g');

    $this->assertDatabaseHas('ingredients', ['name' => 'Beef', 'unit' => 'g']);
});

it('lists ingredients ordered by name', function () {
    Ingredient::factory()->create(['name' => 'Tomato', 'unit' => 'pcs']);
    Ingredient::factory()->create(['name' => 'Beef', 'unit' => 'g']);

    $this->getJson('/api/ingredients')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Beef')
        ->assertJsonPath('data.1.name', 'Tomato');
});

it('rejects an unknown unit', function () {
    $this->postJson('/api/ingredients', ['name' => 'Beef', 'unit' => 'bushel'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['unit']);
});

it('requires a name', function () {
    $this->postJson('/api/ingredients', ['unit' => 'g'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('rejects a duplicate ingredient name', function () {
    Ingredient::factory()->create(['name' => 'Beef']);

    $this->postJson('/api/ingredients', ['name' => 'Beef', 'unit' => 'kg'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});
