<?php

use App\Enums\StockMovementType;
use App\Enums\Unit;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Sale;
use App\Models\StockMovement;

beforeEach(function () {
    $this->beef = Ingredient::factory()->unit(Unit::Gram)->create(['name' => 'Beef']);
    $this->bun = Ingredient::factory()->unit(Unit::Piece)->create(['name' => 'Bun']);
    $this->cheese = Ingredient::factory()->unit(Unit::Gram)->create(['name' => 'Cheese']);

    $this->burger = MenuItem::factory()
        ->withRecipe([$this->beef->id => 150, $this->bun->id => 1, $this->cheese->id => 20])
        ->create(['name' => 'Classic Burger']);
});

function stockIn(Ingredient $ingredient, float $quantity): void
{
    $ingredient->stockMovements()->create(['quantity' => $quantity, 'type' => StockMovementType::Adjustment]);
}

it('consumes ingredients according to the recipe times the quantity sold', function () {
    stockIn($this->beef, 1000);
    stockIn($this->bun, 10);
    stockIn($this->cheese, 500);

    $response = $this->postJson('/api/sales', ['menu_item_id' => $this->burger->id, 'quantity' => 2]);

    $response->assertCreated()
        ->assertJsonPath('data.menu_item_name', 'Classic Burger')
        ->assertJsonPath('data.quantity', 2)
        ->assertJsonPath('replayed', false)
        ->assertJsonPath('warnings', [])
        ->assertJsonCount(3, 'consumed')
        ->assertJsonPath('consumed.0', [
            'ingredient_id' => $this->beef->id, 'ingredient_name' => 'Beef', 'unit' => 'g',
            'quantity' => 300, 'stock_after' => 700,
        ]);

    expect($this->beef->currentStock())->toBe('700.000')
        ->and($this->bun->currentStock())->toBe('8.000')
        ->and($this->cheese->currentStock())->toBe('460.000')
        ->and(StockMovement::where('type', 'sale')->count())->toBe(3)
        ->and(Sale::count())->toBe(1);
});

it('links the negative movements to the sale', function () {
    $this->postJson('/api/sales', ['menu_item_id' => $this->burger->id, 'quantity' => 1])->assertCreated();

    $sale = Sale::sole();

    expect($sale->stockMovements)->toHaveCount(3)
        ->and($sale->stockMovements->pluck('quantity', 'ingredient_id')->map(fn ($q) => (float) $q)->all())
        ->toBe([$this->beef->id => -150.0, $this->bun->id => -1.0, $this->cheese->id => -20.0]);
});

it('accepts a sale that takes stock negative and flags each affected ingredient', function () {
    stockIn($this->beef, 200);   // enough for 1, not for 2
    stockIn($this->bun, 10);
    // cheese: nothing in stock at all

    $response = $this->postJson('/api/sales', ['menu_item_id' => $this->burger->id, 'quantity' => 2]);

    $response->assertCreated()
        ->assertJsonCount(2, 'warnings')
        ->assertJsonPath('warnings.0.ingredient_name', 'Beef')
        ->assertJsonPath('warnings.0.stock_after', -100)
        ->assertJsonPath('warnings.1.ingredient_name', 'Cheese')
        ->assertJsonPath('warnings.1.stock_after', -40);

    expect(Sale::count())->toBe(1)
        ->and($this->beef->currentStock())->toBe('-100.000')
        ->and($this->bun->currentStock())->toBe('8.000')
        ->and($this->cheese->currentStock())->toBe('-40.000');
});

it('rejects a sale of a menu item with no recipe and writes nothing', function () {
    $empty = MenuItem::factory()->create(['name' => 'Mystery Box']);

    $this->postJson('/api/sales', ['menu_item_id' => $empty->id, 'quantity' => 1])
        ->assertUnprocessable()
        ->assertJsonPath('error', 'empty_recipe')
        ->assertJsonPath('menu_item_id', $empty->id);

    expect(Sale::count())->toBe(0)->and(StockMovement::count())->toBe(0);
});

it('rejects invalid sale events', function (array $payload, string $field) {
    $this->postJson('/api/sales', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);

    expect(Sale::count())->toBe(0);
})->with([
    'unknown menu item' => [fn () => ['menu_item_id' => 999, 'quantity' => 1], 'menu_item_id'],
    'zero quantity' => [fn () => ['menu_item_id' => $this->burger->id, 'quantity' => 0], 'quantity'],
    'fractional quantity' => [fn () => ['menu_item_id' => $this->burger->id, 'quantity' => 1.5], 'quantity'],
    'missing quantity' => [fn () => ['menu_item_id' => $this->burger->id], 'quantity'],
]);

it('replays a repeated idempotency key without touching stock again', function () {
    stockIn($this->beef, 1000);

    $payload = ['menu_item_id' => $this->burger->id, 'quantity' => 1, 'idempotency_key' => 'pos-ticket-42'];

    $first = $this->postJson('/api/sales', $payload)->assertCreated()->assertJsonPath('replayed', false);
    $second = $this->postJson('/api/sales', $payload)->assertOk()->assertJsonPath('replayed', true);

    expect($second->json('data.id'))->toBe($first->json('data.id'))
        ->and(Sale::count())->toBe(1)
        ->and(StockMovement::where('type', 'sale')->count())->toBe(3)
        ->and($this->beef->currentStock())->toBe('850.000');
});

it('uses the recipe as it is at the time of the sale', function () {
    $this->postJson('/api/sales', ['menu_item_id' => $this->burger->id, 'quantity' => 1])->assertCreated();

    $this->burger->replaceRecipe([['ingredient_id' => $this->beef->id, 'quantity' => 999]]);

    // History is untouched by the recipe change.
    expect($this->beef->currentStock())->toBe('-150.000')
        ->and($this->cheese->currentStock())->toBe('-20.000');
});

it('lists recent sales newest first with their movements', function () {
    $this->postJson('/api/sales', ['menu_item_id' => $this->burger->id, 'quantity' => 1])->assertCreated();
    $this->postJson('/api/sales', ['menu_item_id' => $this->burger->id, 'quantity' => 3])->assertCreated();

    $this->getJson('/api/sales')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.quantity', 3)
        ->assertJsonCount(3, 'data.0.movements')
        ->assertJsonPath('data.0.movements.0.quantity', -450);
});
