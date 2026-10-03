<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\Unit;
use App\Models\Ingredient;
use App\Models\PurchaseOrder;
use App\Models\Supplier;

beforeEach(function () {
    $this->supplier = Supplier::factory()->create(['name' => 'Fresh Farms']);
    $this->beef = Ingredient::factory()->unit(Unit::Gram)->create(['name' => 'Beef']);
    $this->bun = Ingredient::factory()->unit(Unit::Piece)->create(['name' => 'Bun']);
});

it('creates a purchase order as a draft with its lines', function () {
    $response = $this->postJson('/api/purchase-orders', [
        'supplier_id' => $this->supplier->id,
        'notes' => 'Weekly order',
        'lines' => [
            ['ingredient_id' => $this->beef->id, 'quantity' => 5000],
            ['ingredient_id' => $this->bun->id, 'quantity' => 40],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.status_label', 'Draft')
        ->assertJsonPath('data.is_open', true)
        ->assertJsonPath('data.supplier_name', 'Fresh Farms')
        ->assertJsonPath('data.sent_at', null)
        ->assertJsonCount(2, 'data.lines')
        ->assertJsonPath('data.lines.0.ingredient_name', 'Beef')
        ->assertJsonPath('data.lines.0.quantity_ordered', 5000);

    $this->assertDatabaseHas('purchase_orders', ['supplier_id' => $this->supplier->id, 'status' => 'draft']);
    $this->assertDatabaseCount('purchase_order_lines', 2);
});

it('requires at least one line', function () {
    $this->postJson('/api/purchase-orders', ['supplier_id' => $this->supplier->id, 'lines' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lines']);

    $this->assertDatabaseCount('purchase_orders', 0);
});

it('rejects an unknown supplier', function () {
    $this->postJson('/api/purchase-orders', [
        'supplier_id' => 999,
        'lines' => [['ingredient_id' => $this->beef->id, 'quantity' => 1]],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['supplier_id']);
});

it('rejects a non-positive line quantity', function () {
    $this->postJson('/api/purchase-orders', [
        'supplier_id' => $this->supplier->id,
        'lines' => [['ingredient_id' => $this->beef->id, 'quantity' => -5]],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['lines.0.quantity']);
});

it('shows a purchase order', function () {
    $order = PurchaseOrder::factory()
        ->for($this->supplier)
        ->withLines([$this->beef->id => 1000])
        ->create();

    $this->getJson("/api/purchase-orders/{$order->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $order->id)
        ->assertJsonPath('data.lines.0.quantity_ordered', 1000);
});

it('lists purchase orders newest first and can filter to open ones', function () {
    $draft = PurchaseOrder::factory()->for($this->supplier)->create();
    $closed = PurchaseOrder::factory()->for($this->supplier)->closed()->create();
    $sent = PurchaseOrder::factory()->for($this->supplier)->sent()->create();

    $this->getJson('/api/purchase-orders')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $sent->id);

    $this->getJson('/api/purchase-orders?open=1')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonMissing(['id' => $closed->id]);
});

it('sends a draft purchase order', function () {
    $order = PurchaseOrder::factory()->for($this->supplier)->withLines([$this->beef->id => 1000])->create();

    $this->postJson("/api/purchase-orders/{$order->id}/send")
        ->assertOk()
        ->assertJsonPath('data.status', 'sent');

    expect($order->fresh())
        ->status->toBe(PurchaseOrderStatus::Sent)
        ->sent_at->not->toBeNull();
});

it('refuses to send an order that is not a draft', function (string $state) {
    $order = PurchaseOrder::factory()->for($this->supplier)->{$state}()->create();

    $this->postJson("/api/purchase-orders/{$order->id}/send")
        ->assertStatus(409)
        ->assertJsonPath('error', 'invalid_state_transition')
        ->assertJsonPath('from', $state)
        ->assertJsonPath('to', 'sent');

    expect($order->fresh()->status->value)->toBe($state);
})->with(['sent', 'closed']);

it('returns 404 for an unknown purchase order', function () {
    $this->getJson('/api/purchase-orders/999')->assertNotFound();
    $this->postJson('/api/purchase-orders/999/send')->assertNotFound();
});
