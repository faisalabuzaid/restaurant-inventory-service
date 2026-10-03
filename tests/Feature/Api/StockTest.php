<?php

use App\Enums\Unit;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;

beforeEach(function () {
    $this->supplier = Supplier::factory()->create();
    $this->beef = Ingredient::factory()->unit(Unit::Gram)->create(['name' => 'Beef']);
    $this->bun = Ingredient::factory()->unit(Unit::Piece)->create(['name' => 'Bun']);
    $this->cheese = Ingredient::factory()->unit(Unit::Gram)->create(['name' => 'Cheese']);
    $this->burger = MenuItem::factory()
        ->withRecipe([$this->beef->id => 150, $this->bun->id => 1, $this->cheese->id => 20])
        ->create();
});

it('reports zero stock for ingredients with no movements', function () {
    $this->getJson('/api/stock')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0', [
            'ingredient_id' => $this->beef->id, 'name' => 'Beef', 'unit' => 'g',
            'current_stock' => 0, 'is_negative' => false, 'on_order' => 0,
        ])
        ->assertJsonStructure(['meta' => ['generated_at']]);
});

it('reflects deliveries minus sales, through the public API only', function () {
    $order = PurchaseOrder::factory()->for($this->supplier)->sent()
        ->withLines([$this->beef->id => 1000, $this->bun->id => 20])
        ->create();
    $beefLine = $order->lines()->where('ingredient_id', $this->beef->id)->first();
    $bunLine = $order->lines()->where('ingredient_id', $this->bun->id)->first();

    $this->postJson("/api/purchase-orders/{$order->id}/deliveries", ['lines' => [
        ['purchase_order_line_id' => $beefLine->id, 'quantity' => 600],
        ['purchase_order_line_id' => $bunLine->id, 'quantity' => 20],
    ]])->assertCreated();

    $this->postJson('/api/sales', ['menu_item_id' => $this->burger->id, 'quantity' => 2])->assertCreated();

    $stock = collect($this->getJson('/api/stock')->assertOk()->json('data'))->keyBy('name');

    expect($stock['Beef'])->toMatchArray(['current_stock' => 300, 'is_negative' => false, 'on_order' => 400])
        ->and($stock['Bun'])->toMatchArray(['current_stock' => 18, 'is_negative' => false, 'on_order' => 0])
        ->and($stock['Cheese'])->toMatchArray(['current_stock' => -40, 'is_negative' => true, 'on_order' => 0]);
});

it('counts on_order only from sent and partially received orders', function () {
    PurchaseOrder::factory()->for($this->supplier)->withLines([$this->beef->id => 111])->create();          // draft
    PurchaseOrder::factory()->for($this->supplier)->sent()->withLines([$this->beef->id => 500])->create();  // sent
    PurchaseOrder::factory()->for($this->supplier)->closed()->withLines([$this->beef->id => 999])->create(); // closed

    $partial = PurchaseOrder::factory()->for($this->supplier)->sent()->withLines([$this->beef->id => 300])->create();
    $this->postJson("/api/purchase-orders/{$partial->id}/deliveries", ['lines' => [
        ['purchase_order_line_id' => $partial->lines()->first()->id, 'quantity' => 100],
    ]])->assertCreated()->assertJsonPath('data.status', 'received');

    $beef = collect($this->getJson('/api/stock')->json('data'))->firstWhere('name', 'Beef');

    // 500 (sent) + 200 (remaining on the partially received one)
    expect($beef['on_order'])->toEqual(700)
        ->and($beef['current_stock'])->toEqual(100);
});

it('lists open purchase orders with what is still outstanding per line', function () {
    $order = PurchaseOrder::factory()->for($this->supplier)->sent()
        ->withLines([$this->beef->id => 1000, $this->bun->id => 20])
        ->create();
    PurchaseOrder::factory()->for($this->supplier)->closed()->withLines([$this->cheese->id => 5])->create();

    $this->postJson("/api/purchase-orders/{$order->id}/deliveries", ['lines' => [
        ['purchase_order_line_id' => $order->lines()->first()->id, 'quantity' => 250],
    ]])->assertCreated();

    $this->getJson('/api/purchase-orders?open=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $order->id)
        ->assertJsonPath('data.0.status', 'received')
        ->assertJsonPath('data.0.lines.0.quantity_ordered', 1000)
        ->assertJsonPath('data.0.lines.0.quantity_received', 250)
        ->assertJsonPath('data.0.lines.0.outstanding', 750)
        ->assertJsonPath('data.0.lines.1.outstanding', 20);
});
